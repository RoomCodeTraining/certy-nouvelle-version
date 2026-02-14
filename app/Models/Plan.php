<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'price_monthly',
        'limits_documents',
        'limits_assistant_calls_per_month',
        'limits_ai_tokens_per_month',
        'features',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'is_default' => 'boolean',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public static function default(): ?self
    {
        return self::where('is_default', true)->first();
    }
}
