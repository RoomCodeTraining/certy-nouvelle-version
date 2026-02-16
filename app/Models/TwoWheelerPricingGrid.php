<?php

namespace App\Models;

use App\Enums\TwoWheelerPowerRangeEnum;
use Illuminate\Database\Eloquent\Model;

class TwoWheelerPricingGrid extends Model
{
    protected $table = 'two_wheeler_pricing_grids';

    protected $fillable = [
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

    public function getPowerRangeEnum(): ?TwoWheelerPowerRangeEnum
    {
        return TwoWheelerPowerRangeEnum::tryFrom($this->power_range);
    }
}
