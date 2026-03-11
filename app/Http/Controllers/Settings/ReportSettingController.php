<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Jobs\ExportReportingAttestationsJob;
use App\Models\ReportSetting;
use App\Models\User;
use App\Services\ExternalService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
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
            'external_email' => ['nullable', 'string', 'email'],
            'external_password' => ['nullable', 'string'],
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

        $enabled = $validated['enabled'] ?? false;

        if ($enabled) {
            /** @var \App\Models\User|null $user */
            $user = $request->user();

            if (! $user || ! $user->isRoot()) {
                throw ValidationException::withMessages([
                    'enabled' => 'Seul un utilisateur root peut activer l\'export automatique.',
                ]);
            }

            $hasValidToken = $user->external_token
                && (! $user->external_token_expires_at || ! $user->external_token_expires_at->isPast());

            $email = $validated['external_email'] ?? null;
            $password = $validated['external_password'] ?? null;

            // Si aucun token valide, on exige les identifiants
            if (! $hasValidToken && (! $email || ! $password)) {
                throw ValidationException::withMessages([
                    'external_email' => 'Email et mot de passe externes requis pour activer l\'export automatique.',
                ]);
            }

            // Si des identifiants sont fournis, on génère un token dédié "sans expiration"
            if ($email && $password) {
                $externalService = app(ExternalService::class);

                if (! $externalService->baseUrl) {
                    throw ValidationException::withMessages([
                        'external_email' => 'Service externe non configuré (ASACI_CORE_URL).',
                    ]);
                }

                $authData = $externalService->auth($email, $password);
                $token = $authData['token'] ?? $authData['access_token'] ?? $authData['data']['token'] ?? null;

                if (! $token) {
                    throw ValidationException::withMessages([
                        'external_email' => ['Identifiants externes invalides.'],
                    ]);
                }

                $user->update([
                    'external_token' => $token,
                    'external_token_expires_at' => null,
                ]);
            }
        }

        return redirect()->route('settings.report-period.edit')->with('success', 'Configuration enregistrée.');
    }
}
