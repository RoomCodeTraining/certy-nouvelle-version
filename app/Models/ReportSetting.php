<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportSetting extends Model
{
    protected $table = 'report_settings';

    protected $fillable = [
        'enabled',
        'frequency',
        'day_of_week',
        'day_of_month',
        'time',
        'emails',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'day_of_week' => 'integer',
            'day_of_month' => 'integer',
        ];
    }

    /** @return list<string> */
    public function getEmailsListAttribute(): array
    {
        $raw = $this->emails ?? '';
        if (trim($raw) === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $raw)), fn ($e) => $e !== '' && filter_var($e, FILTER_VALIDATE_EMAIL)));
    }

    public static function get(): self
    {
        $row = self::first();
        if (! $row) {
            return self::create([
                'enabled' => false,
                'frequency' => 'quinzaine',
                'day_of_week' => 1,
                'day_of_month' => 16,
                'time' => '08:00',
                'emails' => config('app.admin_email', ''),
            ]);
        }

        return $row;
    }
}
