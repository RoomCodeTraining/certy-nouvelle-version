<?php

namespace App\Console\Commands;

use App\Jobs\ExportReportingAttestationsJob;
use App\Mail\ReportExportFailedMail;
use App\Models\ReportSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendReportExportCommand extends Command
{
    protected $signature = 'report:send-export';

    protected $description = 'Envoi automatique de l\'export Reporting (quinzaine : 1er et 16 du mois)';

    public function handle(): int
    {
        $setting = ReportSetting::get();
        if (! $setting->enabled) {
            return self::SUCCESS;
        }

        $now = Carbon::now();
        $today = Carbon::today();

        if ($setting->frequency === 'test_5min') {
            if ($now->minute % 5 !== 0) {
                return self::SUCCESS;
            }
            $dateFrom = $today->copy()->subDay()->format('Y-m-d');
            $dateTo = $dateFrom;
            $this->dispatchForPeriod($setting, $dateFrom, $dateTo, 'TEST jour précédent (toutes les 5 min)');

            return self::SUCCESS;
        }

        $configuredTime = $setting->time ? substr((string) $setting->time, 0, 5) : '08:00';
        $currentTime = $now->format('H:i');
        if ($currentTime !== $configuredTime) {
            return self::SUCCESS;
        }
        $day = (int) $today->day;

        if ($setting->frequency === 'quinzaine') {
            if ($day === 1) {
                $dateFrom = $today->copy()->subMonth()->day(16)->format('Y-m-d');
                $dateTo = $today->copy()->subMonth()->endOfMonth()->format('Y-m-d');
                $this->dispatchForPeriod($setting, $dateFrom, $dateTo, '2e quinzaine mois précédent');
            } elseif ($day === 16) {
                $dateFrom = $today->copy()->day(1)->format('Y-m-d');
                $dateTo = $today->copy()->day(15)->format('Y-m-d');
                $this->dispatchForPeriod($setting, $dateFrom, $dateTo, '1ère quinzaine mois en cours');
            }

            return self::SUCCESS;
        }

        if ($setting->frequency === 'daily') {
            $dateFrom = $today->copy()->subDay()->format('Y-m-d');
            $dateTo = $dateFrom;
            $this->dispatchForPeriod($setting, $dateFrom, $dateTo, 'jour précédent');
        } elseif ($setting->frequency === 'weekly' && $setting->day_of_week) {
            $targetDow = (int) $setting->day_of_week;
            $currentDow = (int) $today->dayOfWeekIso;
            if ($currentDow === $targetDow) {
                $dateFrom = $today->copy()->subWeek()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
                $dateTo = $today->copy()->subWeek()->endOfWeek(Carbon::SUNDAY)->format('Y-m-d');
                $this->dispatchForPeriod($setting, $dateFrom, $dateTo, 'semaine précédente');
            }
        } elseif ($setting->frequency === 'monthly' && $setting->day_of_month) {
            $targetDay = (int) $setting->day_of_month;
            if ($day === $targetDay) {
                $dateFrom = $today->copy()->subMonth()->startOfMonth()->format('Y-m-d');
                $dateTo = $today->copy()->subMonth()->endOfMonth()->format('Y-m-d');
                $this->dispatchForPeriod($setting, $dateFrom, $dateTo, 'mois précédent');
            }
        }

        return self::SUCCESS;
    }

    private function dispatchForPeriod(ReportSetting $setting, string $dateFrom, string $dateTo, string $label): void
    {
        $user = User::where('is_root', true)->whereNotNull('external_token')->first();
        if (! $user) {
            $this->sendFailureNotification(
                $setting,
                'Aucun utilisateur root avec token externe.',
                "{$dateFrom} → {$dateTo}",
            );
            $this->warn('Aucun utilisateur root avec token externe. Notification envoyée.');

            return;
        }

        if ($user->external_token_expires_at && $user->external_token_expires_at->isPast()) {
            $this->sendFailureNotification(
                $setting,
                'La session externe (ASACI) a expiré. Veuillez vous reconnecter pour renouveler le token.',
                "{$dateFrom} → {$dateTo}",
            );
            $this->warn('Token expiré. Notification envoyée aux destinataires.');

            return;
        }

        $emails = $setting->emails_list;
        if (empty($emails)) {
            $emails = [config('app.admin_email', 'dsieroger@gmail.com')];
        }

        ExportReportingAttestationsJob::dispatch($user->id, $dateFrom, $dateTo, null, $emails);
        $this->info("Export {$label} ({$dateFrom} → {$dateTo}) dispatché vers " . count($emails) . ' destinataire(s).');
    }

    private function sendFailureNotification(ReportSetting $setting, string $reason, string $period): void
    {
        $emails = $setting->emails_list;
        if (empty($emails)) {
            $emails = [config('app.admin_email', 'dsieroger@gmail.com')];
        }
        $mailable = new ReportExportFailedMail($reason, $period);
        foreach ($emails as $email) {
            $e = trim((string) $email);
            if ($e !== '') {
                Mail::to($e)->send($mailable);
            }
        }
    }
}
