<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Contract extends Model
{
    protected $fillable = [
        'reference',
        'policy_number',
        'organization_id',
        'client_id',
        'vehicle_id',
        'company_id',
        'parent_id',
        'contract_type',
        'status',
        'attestation_issued_at',
        'attestation_number',
        'attestation_link',
        'start_date',
        'end_date',
        'metadata',
        'base_amount',
        'rc_amount',
        'defence_appeal_amount',
        'person_transport_amount',
        'accessory_amount',
        'taxes_amount',
        'cedeao_amount',
        'fga_amount',
        'reduction_amount',
        'reduction_bns',
        'reduction_on_commission',
        'reduction_on_profession_percent',
        'reduction_on_profession_amount',
        'company_accessory',
        'agency_accessory',
        'prime_ttc',
        'commission_amount',
        'reduction_bns_amount',
        'reduction_on_commission_amount',
        'reduction_on_profession_amount_stored',
        'total_reduction_amount',
        'total_amount',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'attestation_issued_at' => 'datetime',
            'metadata' => 'array',
            'base_amount' => 'integer',
            'rc_amount' => 'integer',
            'defence_appeal_amount' => 'integer',
            'person_transport_amount' => 'integer',
            'accessory_amount' => 'integer',
            'taxes_amount' => 'integer',
            'cedeao_amount' => 'integer',
            'fga_amount' => 'integer',
            'reduction_amount' => 'integer',
            'reduction_bns' => 'decimal:2',
            'reduction_on_commission' => 'decimal:2',
            'reduction_on_profession_percent' => 'decimal:2',
            'reduction_on_profession_amount' => 'integer',
            'company_accessory' => 'integer',
            'agency_accessory' => 'integer',
            'prime_ttc' => 'integer',
            'commission_amount' => 'integer',
            'reduction_bns_amount' => 'integer',
            'reduction_on_commission_amount' => 'integer',
            'reduction_on_profession_amount_stored' => 'integer',
            'total_reduction_amount' => 'integer',
            'total_amount' => 'integer',
        ];
    }

    public const TYPE_VP = 'VP';
    public const TYPE_TPC = 'TPC';
    public const TYPE_TPM = 'TPM';
    public const TYPE_TWO_WHEELER = 'TWO_WHEELER';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_VALIDATED = 'validated';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_EXPIRED = 'expired';

    /** Préfixe et longueur après le tiret : 11 caractères alphanumériques majuscules (ex. CA-A7K9M2X4B1Q). */
    public const REFERENCE_PREFIX = 'CA-';

    public const REFERENCE_SUFFIX_LENGTH = 11;

    /**
     * Génère une référence unique : CA- + 11 caractères alphanumériques majuscules.
     */
    public static function generateUniqueReference(): string
    {
        do {
            $ref = self::REFERENCE_PREFIX . strtoupper(Str::random(self::REFERENCE_SUFFIX_LENGTH));
        } while (self::query()->where('reference', $ref)->exists());

        return $ref;
    }

    /**
     * Périmètre d'accès : contrats dont le client est accessible par l'utilisateur (root = tous via org, non-root = owner_id).
     */
    public function scopeAccessibleBy(Builder $query, \App\Models\User $user): Builder
    {
        return $query->whereIn('client_id', Client::query()->accessibleBy($user)->select('id'));
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** Contrat de base (nouvelle affaire) dont ce contrat est le renouvellement. */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'parent_id');
    }

    /** Renouvellements (contrats enfants) de ce contrat. */
    public function children(): HasMany
    {
        return $this->hasMany(Contract::class, 'parent_id');
    }

    /** True si nouvelle affaire (premier contrat du véhicule), false si renouvellement. */
    public function isNewBusiness(): bool
    {
        return $this->parent_id === null;
    }

    public function histories(): HasMany
    {
        return $this->hasMany(ContractHistory::class);
    }

    /**
     * Montant total de la prime (somme des composantes), en FCFA.
     */
    public function getTotalPremiumAttribute(): ?int
    {
        $fields = [
            'base_amount', 'rc_amount', 'defence_appeal_amount', 'person_transport_amount',
            'accessory_amount', 'taxes_amount', 'cedeao_amount', 'fga_amount',
        ];
        $total = 0;
        foreach ($fields as $field) {
            $v = $this->getAttribute($field);
            if ($v !== null) {
                $total += (int) $v;
            }
        }

        return $total > 0 ? $total : null;
    }

    /** Prime (somme hors accessoire) + accessoire, en FCFA. */
    public function getPrimeWithAccessoryAttribute(): ?int
    {
        $t = $this->total_premium;
        return $t;
    }

    /**
     * Montant total avant réductions (prime TTC) : prime grille + accessoire compagnie + accessoire agence.
     * Utilise la valeur stockée prime_ttc si présente, sinon calcule.
     */
    public function getTotalBeforeReductionAttribute(): ?int
    {
        $stored = $this->getRawOriginal('prime_ttc');
        if ($stored !== null && (int) $stored > 0) {
            return (int) $stored;
        }
        $prime = $this->total_premium;
        if ($prime === null) {
            return null;
        }
        $company = (int) ($this->company_accessory ?? 0);
        $agency = (int) ($this->agency_accessory ?? 0);

        return $prime + $company + $agency;
    }

    /**
     * Total de toutes les réductions en FCFA.
     * Utilise la valeur stockée total_reduction_amount si présente, sinon calcule à partir des %.
     */
    public function getTotalReductionAmountAttribute(): int
    {
        $stored = $this->getRawOriginal('total_reduction_amount');
        if ($stored !== null && (int) $stored > 0) {
            return (int) $stored;
        }
        $before = $this->getTotalBeforeReductionAttribute();
        if ($before === null || $before <= 0) {
            return 0;
        }
        $r = 0;
        $bns = (float) ($this->reduction_bns ?? 0);
        if ($bns > 0) {
            $r += (int) round($before * ($bns / 100));
        }
        $comm = (float) ($this->reduction_on_commission ?? 0);
        if ($comm > 0) {
            $r += (int) round($before * ($comm / 100));
        }
        $profPct = (float) ($this->reduction_on_profession_percent ?? 0);
        if ($profPct > 0) {
            $r += (int) round($before * ($profPct / 100));
        }
        $r += (int) ($this->reduction_on_profession_amount ?? 0);
        $r += (int) ($this->reduction_amount ?? 0);

        return $r;
    }

    /**
     * Montant total final = prime TTC + commission - réductions.
     * Utilise la valeur stockée total_amount si présente, sinon calcule.
     */
    public function getTotalAfterReductionAttribute(): ?int
    {
        $stored = $this->getRawOriginal('total_amount');
        if ($stored !== null) {
            return max(0, (int) $stored);
        }
        $before = $this->getTotalBeforeReductionAttribute();
        if ($before === null) {
            return null;
        }
        $commission = (int) ($this->commission_amount ?? 0);
        return max(0, $before + $commission - $this->getTotalReductionAmountAttribute());
    }

    /**
     * Calcule et remplit tous les montants stockés : prime_ttc, réductions en FCFA, total_reduction_amount, total_amount.
     * Formule : total_amount = prime_ttc + commission_amount - total_reduction_amount.
     */
    public function computeAndFillStoredAmounts(): void
    {
        $prime = $this->total_premium;
        if ($prime === null) {
            return;
        }
        $company = (int) ($this->company_accessory ?? 0);
        $agency = (int) ($this->agency_accessory ?? 0);
        $primeTtc = $prime + $company + $agency;
        $commission = (int) ($this->commission_amount ?? 0);

        $bnsPct = (float) ($this->reduction_bns ?? 0);
        $reductionBnsAmount = $bnsPct > 0 ? (int) round($primeTtc * ($bnsPct / 100)) : 0;

        $commPct = (float) ($this->reduction_on_commission ?? 0);
        $reductionOnCommissionAmount = $commPct > 0 ? (int) round($primeTtc * ($commPct / 100)) : 0;

        $profPct = (float) ($this->reduction_on_profession_percent ?? 0);
        $profFixed = (int) ($this->reduction_on_profession_amount ?? 0);
        $reductionOnProfAmount = $profPct > 0
            ? (int) round($primeTtc * ($profPct / 100))
            : $profFixed;

        $reductionOther = (int) ($this->reduction_amount ?? 0);
        $totalReduction = $reductionBnsAmount + $reductionOnCommissionAmount + $reductionOnProfAmount + $reductionOther;
        $totalAmount = max(0, $primeTtc + $commission - $totalReduction);

        $this->forceFill([
            'prime_ttc' => $primeTtc,
            'reduction_bns_amount' => $reductionBnsAmount,
            'reduction_on_commission_amount' => $reductionOnCommissionAmount,
            'reduction_on_profession_amount_stored' => $reductionOnProfAmount,
            'total_reduction_amount' => $totalReduction,
            'total_amount' => $totalAmount,
        ]);
        $this->saveQuietly();
    }
}
