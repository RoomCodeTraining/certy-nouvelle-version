<?php

namespace App\Console\Commands;

use App\Jobs\ExportReportingAttestationsJob;
use App\Models\ReportSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

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
        $configuredTime = $setting->time ? substr((string) $setting->time, 0, 5) : '08:00';
        $currentTime = $now->format('H:i');
        if ($currentTime !== $configuredTime) {
            return self::SUCCESS;
        }

        $today = Carbon::today();
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
            $this->warn('Aucun utilisateur root avec token externe. Export non envoyé.');

            return;
        }

        $emails = $setting->emails_list;
        if (empty($emails)) {
            $emails = [config('app.admin_email', 'dsieroger@gmail.com')];
        }

        ExportReportingAttestationsJob::dispatch($user->id, $dateFrom, $dateTo, null, $emails);
        $this->info("Export {$label} ({$dateFrom} → {$dateTo}) dispatché vers " . count($emails) . ' destinataire(s).');
    }
}
