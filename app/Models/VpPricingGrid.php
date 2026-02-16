<?php

namespace App\Models;

use App\Enums\ContractDurationEnum;
use App\Enums\VpPowerRangeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VpPricingGrid extends Model
{
    protected $table = 'vp_pricing_grids';

    protected $fillable = [
        'energy_source_id',
        'duration',
        'power_range',
        'base_amount',
        'rc_amount',
        'defence_appeal_amount',
        'person_transport_amount',
        'accessory_amount',
        'taxes_amount',
        'cedeao_amount',
        'fga_amount',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function energySource(): BelongsTo
    {
        return $this->belongsTo(EnergySource::class);
    }

    public function getDurationEnum(): ?ContractDurationEnum
    {
        return ContractDurationEnum::tryFrom($this->duration);
    }

    public function getPowerRangeEnum(): ?VpPowerRangeEnum
    {
        return VpPowerRangeEnum::tryFrom($this->power_range);
    }
}
