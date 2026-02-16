<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    protected $fillable = [
        'client_id',
        'pricing_type',
        'vehicle_brand_id',
        'vehicle_model_id',
        'body_type',
        'registration_number',
        'circulation_zone_id',
        'energy_source_id',
        'vehicle_usage_id',
        'vehicle_type_id',
        'vehicle_category_id',
        'vehicle_gender_id',
        'color_id',
        'fiscal_power',
        'payload_capacity',
        'engine_capacity',
        'seat_count',
        'year_of_first_registration',
        'first_registration_date',
        'registration_card_number',
        'chassis_number',
        'new_value',
        'replacement_value',
    ];

    /**
     * Périmètre d'accès : véhicules dont le client est accessible par l'utilisateur (root = tous via org, non-root = owner_id).
     */
    public function scopeAccessibleBy(Builder $query, \App\Models\User $user): Builder
    {
        return $query->whereIn('client_id', Client::query()->accessibleBy($user)->select('id'));
    }

    protected function casts(): array
    {
        return [
            'fiscal_power' => 'integer',
            'year_of_first_registration' => 'integer',
            'payload_capacity' => 'decimal:2',
            'engine_capacity' => 'integer',
            'seat_count' => 'integer',
            'first_registration_date' => 'date',
            'new_value' => 'decimal:2',
            'replacement_value' => 'decimal:2',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(VehicleBrand::class, 'vehicle_brand_id');
    }

    public function model(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class, 'vehicle_model_id');
    }

    public function circulationZone(): BelongsTo
    {
        return $this->belongsTo(CirculationZone::class, 'circulation_zone_id');
    }

    public function energySource(): BelongsTo
    {
        return $this->belongsTo(EnergySource::class, 'energy_source_id');
    }

    public function vehicleUsage(): BelongsTo
    {
        return $this->belongsTo(VehicleUsage::class, 'vehicle_usage_id');
    }

    public function vehicleType(): BelongsTo
    {
        return $this->belongsTo(VehicleType::class, 'vehicle_type_id');
    }

    public function vehicleCategory(): BelongsTo
    {
        return $this->belongsTo(VehicleCategory::class, 'vehicle_category_id');
    }

    public function vehicleGender(): BelongsTo
    {
        return $this->belongsTo(VehicleGender::class, 'vehicle_gender_id');
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(Color::class, 'color_id');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    /** Types pour le calcul des primes (grilles tarifaires) */
    public const PRICING_VP = 'VP';
    public const PRICING_TPC = 'TPC';
    public const PRICING_TPM = 'TPM';
    public const PRICING_TWO_WHEELER = 'TWO_WHEELER';
}
