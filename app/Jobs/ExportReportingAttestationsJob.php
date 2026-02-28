<?php

namespace App\Jobs;

use App\Mail\ReportingExportReadyMail;
use App\Models\User;
use App\Services\ExternalService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportReportingAttestationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 600;

    public function __construct(
        public int $userId,
        public ?string $dateFrom,
        public ?string $dateTo,
        public ?string $search = null,
    ) {}

    public function handle(ExternalService $externalService): void
    {
        $user = User::find($this->userId);
        if (! $user || ! $user->external_token) {
            Log::warning('ExportReportingAttestationsJob: user or token missing', ['user_id' => $this->userId]);
            return;
        }

        $printedAt = ($this->dateFrom && $this->dateTo)
            ? $externalService->buildPrintedAtInclusive($this->dateFrom, $this->dateTo)
            : null;

        $filters = ['per_page' => 500];
        if ($printedAt) {
            $filters['printed_at'] = $printedAt;
        }
        if ($this->search) {
            $filters['search'] = $this->search;
        }

        $result = $externalService->getCertificatesExport($user->external_token, $filters);
        if (isset($result['errors'])) {
            Log::error('ExportReportingAttestationsJob: API error', [
                'user_id' => $this->userId,
                'errors' => $result['errors'],
            ]);
            return;
        }

        $rows = $result['data'] ?? [];
        $path = $this->buildExcel($rows);

        if (! $path) {
            Log::error('ExportReportingAttestationsJob: failed to build Excel', ['user_id' => $this->userId]);
            return;
        }

        try {
            $adminEmail = config('app.admin_email', 'dsieroger@gmail.com');
            Mail::to($adminEmail)->send(new ReportingExportReadyMail($path, $this->dateFrom, $this->dateTo, count($rows)));
        } finally {
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }

    private function buildExcel(array $rows): ?string
    {
        $filename = 'reporting-attestations-externes-' . now()->format('Y-m-d-His') . '.xlsx';
        $path = storage_path('app/exports/' . $filename);

        $dir = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Reporting');

            $headers = [
                'Date d\'émission',
                'Référence',
                'Assuré',
                'Plaque',
                'Période début',
                'Période fin',
                'Organisation',
                'Bureau',
                'Type',
                'État',
            ];

            $columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];
            foreach ($headers as $index => $header) {
                $sheet->setCellValue($columns[$index] . '1', $header);
            }

            $rowIndex = 2;
            foreach ($rows as $row) {
                $printedAt = $row['printed_at'] ?? ($row['issued_at'] ?? ($row['created_at'] ?? null));
                $start = $row['starts_at'] ?? ($row['start_date'] ?? ($row['period_start'] ?? ($row['effective_date'] ?? null)));
                $end = $row['ends_at'] ?? ($row['end_date'] ?? ($row['period_end'] ?? ($row['expiry_date'] ?? null)));
                $insured = $row['insured_name'] ?? ($row['assure'] ?? ($row['insured'] ?? ($row['policy_holder'] ?? '')));
                $plaque = $row['licence_plate'] ?? ($row['plaque'] ?? ($row['registration_number'] ?? ($row['immat'] ?? '')));

                $organization = '';
                if (isset($row['organization']) && is_array($row['organization'])) {
                    $organization = $row['organization']['name'] ?? ($row['organization']['code'] ?? '');
                }
                $office = '';
                if (isset($row['office']) && is_array($row['office'])) {
                    $office = $row['office']['name'] ?? ($row['office']['code'] ?? '');
                }
                $typeLabel = '';
                if (isset($row['certificate_variant']) && is_array($row['certificate_variant'])) {
                    $typeLabel = $row['certificate_variant']['name'] ?? ($row['certificate_variant']['code'] ?? '');
                } elseif (isset($row['certificate_type']) && is_array($row['certificate_type'])) {
                    $typeLabel = $row['certificate_type']['name'] ?? ($row['certificate_type']['code'] ?? '');
                } else {
                    $typeLabel = $row['type'] ?? '';
                }
                $etat = '';
                if (isset($row['state']) && is_array($row['state'])) {
                    $etat = $row['state']['label'] ?? ($row['state']['name'] ?? '');
                } else {
                    $etat = $row['status'] ?? ($row['etat'] ?? '');
                }

                $sheet->setCellValue("A{$rowIndex}", $printedAt);
                $sheet->setCellValue("B{$rowIndex}", $row['reference'] ?? ($row['id'] ?? ''));
                $sheet->setCellValue("C{$rowIndex}", $insured);
                $sheet->setCellValue("D{$rowIndex}", $plaque);
                $sheet->setCellValue("E{$rowIndex}", $start);
                $sheet->setCellValue("F{$rowIndex}", $end);
                $sheet->setCellValue("G{$rowIndex}", $organization);
                $sheet->setCellValue("H{$rowIndex}", $office);
                $sheet->setCellValue("I{$rowIndex}", $typeLabel);
                $sheet->setCellValue("J{$rowIndex}", $etat);
                $rowIndex++;
            }

            foreach ($columns as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save($path);
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            return $path;
        } catch (\Throwable $e) {
            Log::error('ExportReportingAttestationsJob: buildExcel failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }
}
