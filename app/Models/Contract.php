<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Contract extends Model
{
    /** Affichage PDF avenant : seule la ligne CEDEAO (FCFA). */
    public const PDF_ENDORSEMENT_CEDEAO_FCFA = 1000;

    protected $fillable = [
        'reference',
        'policy_number',
        'organization_id',
        'created_by_id',
        'updated_by_id',
        'client_id',
        'vehicle_id',
        'second_vehicle_id',
        'company_id',
        'parent_id',
        'endorsement_type',
        'is_double_cabine',
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
        'optional_guarantees_amount',
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
            'is_double_cabine' => 'boolean',
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
            'optional_guarantees_amount' => 'integer',
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
            $ref = self::REFERENCE_PREFIX.strtoupper(Str::random(self::REFERENCE_SUFFIX_LENGTH));
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function secondVehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'second_vehicle_id');
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

    /** Contrat avenant (souscription ou avenant « garanties »). */
    public function isEndorsementContract(): bool
    {
        if (! empty($this->endorsement_type)) {
            return true;
        }

        $meta = $this->metadata;
        if (! is_array($meta)) {
            return false;
        }

        if (! empty($meta['endorsement_type'] ?? null)) {
            return true;
        }

        return ($meta['creation_mode'] ?? null) === 'endorsement';
    }

    /**
     * Requête alignée sur isEndorsementContract() (colonne + metadata).
     */
    public function scopeWhereEndorsementContract(Builder $query): void
    {
        $query->where(function (Builder $w) {
            $w->where(function (Builder $a) {
                $a->whereNotNull('endorsement_type')
                    ->where('endorsement_type', '!=', '');
            })->orWhere('metadata->creation_mode', 'endorsement')
                ->orWhere(function (Builder $b) {
                    $b->whereNotNull('metadata->endorsement_type')
                        ->where('metadata->endorsement_type', '!=', '');
                });
        });
    }

    /**
     * @param  'new'|'endorsement'|'renewal'  $scope  Filtre liste contrats (URL deal_scope).
     */
    public function scopeForDealScope(Builder $query, string $scope): void
    {
        match ($scope) {
            'new' => $query->whereNull('parent_id'),
            'endorsement' => $query->whereNotNull('parent_id')->whereEndorsementContract(),
            'renewal' => $query->whereNotNull('parent_id')
                ->whereNot(fn (Builder $q) => $q->whereEndorsementContract()),
            default => null,
        };
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
            'base_amount',
            'rc_amount',
            'defence_appeal_amount',
            'person_transport_amount',
            'accessory_amount',
            'taxes_amount',
            'cedeao_amount',
            'fga_amount',
            'optional_guarantees_amount',
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
     * Utilise la valeur stockée total_reduction_amount si présente, sinon appelle computeAndFillStoredAmounts.
     */
    public function getTotalReductionAmountAttribute(): int
    {
        $stored = $this->getRawOriginal('total_reduction_amount');
        if ($stored !== null && (int) $stored >= 0) {
            return (int) $stored;
        }
        $this->computeAndFillStoredAmounts();

        return (int) ($this->getRawOriginal('total_reduction_amount') ?? 0);
    }

    /**
     * Prime nette (base pour calcul commission bordereau) = RC + DR + TP + optional - réductions + accessoire.
     * Montant avant taxes, FGA, CEDEAO. En FCFA.
     */
    public function getPrimeNetteForCommissionAttribute(): ?int
    {
        $primeNette = (int) ($this->rc_amount ?? 0)
            + (int) ($this->defence_appeal_amount ?? 0)
            + (int) ($this->person_transport_amount ?? 0)
            + (int) ($this->optional_guarantees_amount ?? 0);
        $totalReduction = $this->getTotalReductionAmountAttribute();
        $montantApresReduction = max(0, $primeNette - $totalReduction);

        return $montantApresReduction + (int) ($this->accessory_amount ?? 0);
    }

    /**
     * Montant total final (Prime TTC) = montant_apres_reduction + accessory + taxes + fga + cedao.
     * Utilise la valeur stockée total_amount si présente, sinon calcule.
     */
    public function getTotalAfterReductionAttribute(): ?int
    {
        $stored = $this->getRawOriginal('total_amount');
        if ($stored !== null) {
            return max(0, (int) $stored);
        }
        $this->computeAndFillStoredAmounts();

        return max(0, (int) ($this->getRawOriginal('total_amount') ?? 0));
    }

    /**
     * Calcule et remplit tous les montants stockés.
     * Chaque réduction (BNS, commission, profession) s'applique sur chaque garantie ;
     * prime_nette = somme des garanties après réduction.
     * Taxe = 14,5 % de (prime_nette + accessoire).
     * Taxe FGA = 2 % de la RC après réduction si réduction, sinon grille.
     */
    public function computeAndFillStoredAmounts(): void
    {
        $amounts = $this->getGuaranteeAmountsArray();
        $totalGuarantees = array_sum($amounts);

        $bnsPct = (float) ($this->reduction_bns ?? 0);
        $commPct = (float) ($this->reduction_on_commission ?? 0);
        $profPct = (float) ($this->reduction_on_profession_percent ?? 0);
        $profFixed = (int) ($this->reduction_on_profession_amount ?? 0);
        $reductionOther = (int) ($this->reduction_amount ?? 0);
        $totalPct = $bnsPct + $commPct + $profPct;

        $primeNetteBeforeReduction = $totalGuarantees;
        $primeNette = 0;
        $rcAfterReduction = (int) ($this->rc_amount ?? 0);

        foreach ($amounts as $key => $amount) {
            if ($totalGuarantees <= 0) {
                $reduced = $amount;
            } else {
                $reduced = $amount;
                if ($totalPct > 0) {
                    $reduced = max(0, $amount - (int) round($amount * $totalPct / 100));
                }
                if ($profFixed > 0) {
                    $share = (int) round(($amount / $totalGuarantees) * $profFixed);
                    $reduced = max(0, $reduced - $share);
                }
            }
            $primeNette += $reduced;
            if ($key === 'rc') {
                $rcAfterReduction = $reduced;
            }
        }

        $reductionBnsAmount = $bnsPct > 0 ? (int) round($primeNetteBeforeReduction * ($bnsPct / 100)) : 0;
        $reductionOnCommissionAmount = $commPct > 0 ? (int) round($primeNetteBeforeReduction * ($commPct / 100)) : 0;
        $reductionOnProfAmount = $profPct > 0
            ? (int) round($primeNetteBeforeReduction * ($profPct / 100))
            : $profFixed;
        $totalReduction = $primeNetteBeforeReduction - $primeNette + $reductionOther;
        $montantApresReduction = max(0, $primeNette - $reductionOther);

        $accessoryForTax = (int) ($this->accessory_amount ?? 0);
        $taxesAmount = (int) round(($montantApresReduction + $accessoryForTax) * 0.145);

        $hasReduction = $totalPct > 0 || $profFixed > 0 || $reductionOther > 0;
        $fgaAmount = $hasReduction && $rcAfterReduction > 0
            ? (int) round($rcAfterReduction * 0.02)
            : (int) ($this->fga_amount ?? 0);

        $totalAmount = $montantApresReduction
            + $accessoryForTax
            + $taxesAmount
            + $fgaAmount
            + (int) ($this->cedeao_amount ?? 0);

        $this->forceFill([
            'prime_ttc' => $totalAmount,
            'reduction_bns_amount' => $reductionBnsAmount,
            'reduction_on_commission_amount' => $reductionOnCommissionAmount,
            'reduction_on_profession_amount_stored' => $reductionOnProfAmount,
            'total_reduction_amount' => $totalReduction,
            'taxes_amount' => $taxesAmount,
            'fga_amount' => $fgaAmount,
            'total_amount' => $totalAmount,
        ]);
        $this->saveQuietly();
    }

    /** Retourne les montants par garantie : rc, dr, tp, optionnelles. */
    private function getGuaranteeAmountsArray(): array
    {
        $result = [];
        $rc = (int) ($this->rc_amount ?? 0);
        $dr = (int) ($this->defence_appeal_amount ?? 0);
        $tp = (int) ($this->person_transport_amount ?? 0);
        if ($rc > 0) {
            $result['rc'] = $rc;
        }
        if ($dr > 0) {
            $result['dr'] = $dr;
        }
        if ($tp > 0) {
            $result['tp'] = $tp;
        }
        $optional = $this->metadata['optional_guarantees'] ?? [];
        $optSum = 0;
        if (is_array($optional)) {
            foreach ($optional as $g) {
                $amt = (int) ($g['amount'] ?? 0);
                if ($amt > 0) {
                    $result['opt_'.($g['code'] ?? count($result))] = $amt;
                    $optSum += $amt;
                }
            }
        }
        $optTotal = (int) ($this->optional_guarantees_amount ?? 0);
        if ($optTotal > 0 && $optSum === 0) {
            $result['optional'] = $optTotal;
        } elseif ($optTotal > $optSum) {
            $result['optional'] = $optTotal - $optSum;
        }

        return $result;
    }

    /**
     * Retourne les garanties avec montants réduits pour affichage PDF (chaque garantie réduite, somme = prime nette).
     */
    public function getGuaranteeAmountsReducedForDisplay(): array
    {
        $amounts = $this->getGuaranteeAmountsArray();
        $totalGuarantees = array_sum($amounts);
        $bnsPct = (float) ($this->reduction_bns ?? 0);
        $commPct = (float) ($this->reduction_on_commission ?? 0);
        $profPct = (float) ($this->reduction_on_profession_percent ?? 0);
        $profFixed = (int) ($this->reduction_on_profession_amount ?? 0);
        $totalPct = $bnsPct + $commPct + $profPct;

        $labels = ['rc' => 'Responsabilité Civile', 'dr' => 'Défense et Recours', 'tp' => 'Transport de personnes'];
        $optional = $this->metadata['optional_guarantees'] ?? [];
        $result = [];

        foreach ($amounts as $key => $amount) {
            $reduced = $amount;
            if ($totalGuarantees > 0) {
                if ($totalPct > 0) {
                    $reduced = max(0, $amount - (int) round($amount * $totalPct / 100));
                }
                if ($profFixed > 0) {
                    $share = (int) round(($amount / $totalGuarantees) * $profFixed);
                    $reduced = max(0, $reduced - $share);
                }
            }
            $code = match ($key) {
                'rc' => 'RC',
                'dr' => 'DR',
                'tp' => 'TP',
                default => 'AG',
            };
            $label = $labels[$key] ?? null;
            if (! $label && str_starts_with((string) $key, 'opt_')) {
                $codePart = substr((string) $key, 4);
                foreach ($optional as $g) {
                    if (($g['code'] ?? '') === $codePart) {
                        $code = $g['code'] ?? 'AG';
                        $label = $g['label'] ?? 'Autre garantie';
                        break;
                    }
                }
                $label = $label ?? 'Autre garantie';
            } elseif (! $label && $key === 'optional') {
                $label = 'Autres garanties';
            } else {
                $label = $label ?? $key;
            }
            $result[] = ['code' => $code, 'label' => $label, 'amount' => $reduced];
        }

        return $result;
    }

    /**
     * Prime nette pour affichage (somme des garanties après réduction).
     */
    public function getPrimeNetteForDisplay(): int
    {
        $reduced = $this->getGuaranteeAmountsReducedForDisplay();

        return (int) array_sum(array_column($reduced, 'amount'));
    }

    /**
     * Montants d'affichage alignés entre tableaux, fiche détail et PDF.
     * Source unique de vérité pour garantir la cohérence des chiffres.
     */
    public function getDisplayAmounts(): array
    {
        $guaranteesReduced = $this->getGuaranteeAmountsReducedForDisplay();
        $primeNette = (int) array_sum(array_column($guaranteesReduced, 'amount'));
        $reductionAutre = (int) ($this->reduction_amount ?? 0);
        $montantApresReduction = max(0, $primeNette - $reductionAutre);

        return [
            'guarantee_amounts_reduced' => $guaranteesReduced,
            'prime_nette' => $primeNette,
            'montant_apres_reduction' => $montantApresReduction,
            'taxes_amount' => (int) ($this->taxes_amount ?? 0),
            'fga_amount' => (int) ($this->fga_amount ?? 0),
            'cedeao_amount' => (int) ($this->cedeao_amount ?? 0),
            'accessory_amount' => (int) ($this->accessory_amount ?? 0),
            'total_amount' => (int) ($this->total_amount ?? $this->prime_ttc ?? 0),
        ];
    }
}
