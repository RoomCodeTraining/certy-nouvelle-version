<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bordereau extends Model
{
    protected $table = 'bordereaux';

    protected $fillable = [
        'organization_id',
        'company_id',
        'reference',
        'status',
        'period_start',
        'period_end',
        'total_amount',
        'total_commission',
        'commission_pct',
        'submitted_at',
        'approved_at',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'total_amount' => 'decimal:2',
            'total_commission' => 'decimal:2',
            'commission_pct' => 'decimal:2',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public const STATUS_DRAFT = 'draft';
    public const STATUS_VALIDATED = 'validated';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_PAID = 'paid';

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** Préfixe et longueur après le tiret : 11 caractères alphanumériques majuscules (ex. BR-A7K9M2X4B1Q). */
    public const REFERENCE_PREFIX = 'BR-';

    public const REFERENCE_SUFFIX_LENGTH = 11;

    /**
     * Génère une référence unique : BR- + 11 caractères alphanumériques majuscules.
     */
    public static function generateUniqueReference(): string
    {
        do {
            $ref = self::REFERENCE_PREFIX . strtoupper(\Illuminate\Support\Str::random(self::REFERENCE_SUFFIX_LENGTH));
        } while (self::query()->where('reference', $ref)->exists());

        return $ref;
    }

    /**
     * Accessor : si reference en base est vide, génère un affichage (non persisté) pour compatibilité.
     */
    public function getReferenceAttribute(?string $value): string
    {
        return $value !== null && $value !== '' ? $value : self::REFERENCE_PREFIX . strtoupper(\Illuminate\Support\Str::random(self::REFERENCE_SUFFIX_LENGTH));
    }
}
