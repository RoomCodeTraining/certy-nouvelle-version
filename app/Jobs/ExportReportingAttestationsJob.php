<?php

namespace App\Jobs;

use App\Mail\ReportingExportReadyMail;
use App\Models\User;
use App\Services\ExportAttestationsHelper;
use App\Services\ExternalService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportReportingAttestationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 600;

    /**
     * @param  list<string>|null  $emails  Si fourni, envoi à ces emails ; sinon admin_email
     */
    public function __construct(
        public int $userId,
        public ?string $dateFrom,
        public ?string $dateTo,
        public ?string $search = null,
        public ?array $emails = null,
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
            $recipients = $this->emails && count($this->emails) > 0
                ? $this->emails
                : [config('app.admin_email', 'dsieroger@gmail.com')];
            foreach ($recipients as $email) {
                $e = trim((string) $email);
                if ($e !== '') {
                    Mail::to($e)->send(new ReportingExportReadyMail($path, $this->dateFrom, $this->dateTo, count($rows)));
                }
            }
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

            $activeCols = ExportAttestationsHelper::activeColumns($rows);
            $defs = ExportAttestationsHelper::columnDefinitions();
            $colIndex = 0;
            foreach ($activeCols as $key) {
                $col = Coordinate::stringFromColumnIndex($colIndex + 1);
                $sheet->setCellValue($col . '1', $defs[$key] ?? $key);
                $colIndex++;
            }

            $rowIndex = 2;
            foreach ($rows as $row) {
                $values = ExportAttestationsHelper::rowToExportValues(is_array($row) ? $row : []);
                $colIndex = 0;
                foreach ($activeCols as $key) {
                    $col = Coordinate::stringFromColumnIndex($colIndex + 1);
                    $sheet->setCellValue($col . $rowIndex, $values[$key] ?? '');
                    $colIndex++;
                }
                $rowIndex++;
            }

            for ($i = 0; $i < count($activeCols); $i++) {
                $col = Coordinate::stringFromColumnIndex($i + 1);
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
