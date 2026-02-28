<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Jobs\ExportReportingAttestationsJob;
use App\Models\ReportSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class ReportSettingController extends Controller
{
    public function edit(Request $request): Response
    {
        $setting = ReportSetting::get();

        return Inertia::render('Settings/ReportPeriod', [
            'setting' => [
                'id' => $setting->id,
                'enabled' => $setting->enabled,
                'frequency' => $setting->frequency,
                'day_of_week' => $setting->day_of_week,
                'day_of_month' => $setting->day_of_month,
                'time' => substr((string) $setting->time, 0, 5),
                'emails' => $setting->emails ?? '',
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'enabled' => ['boolean'],
            'frequency' => ['required', 'string', 'in:quinzaine,daily,weekly,monthly,test_5min'],
            'day_of_week' => ['nullable', 'integer', 'min:1', 'max:7'],
            'day_of_month' => ['nullable', 'integer', 'min:1', 'max:31'],
            'time' => ['required', 'string', 'regex:/^\d{2}:\d{2}$/'],
            'emails' => ['nullable', 'string', 'max:2000'],
        ]);

        $setting = ReportSetting::get();
        $emailsRaw = $validated['emails'] ?? '';
        $emailsList = array_values(array_filter(array_map('trim', explode(',', $emailsRaw)), fn ($e) => $e !== '' && filter_var($e, FILTER_VALIDATE_EMAIL)));

        $setting->update([
            'enabled' => $validated['enabled'] ?? false,
            'frequency' => $validated['frequency'],
            'day_of_week' => $validated['day_of_week'] ?? null,
            'day_of_month' => $validated['day_of_month'] ?? null,
            'time' => $validated['time'] . ':00',
            'emails' => implode(', ', $emailsList) ?: null,
        ]);

        return redirect()->route('settings.report-period.edit')->with('success', 'Configuration enregistrée.');
    }
}
