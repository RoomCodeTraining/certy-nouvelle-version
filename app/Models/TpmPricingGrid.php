<?php

namespace App\Models;

use App\Enums\ContractDurationEnum;
use App\Enums\TpmPayloadRangeEnum;
use Illuminate\Database\Eloquent\Model;

class TpmPricingGrid extends Model
{
    protected $table = 'tpm_pricing_grids';

    protected $fillable = [
        'duration',
        'payload_range',
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

    public function getDurationEnum(): ?ContractDurationEnum
    {
        return ContractDurationEnum::tryFrom($this->duration);
    }

    public function getPayloadRangeEnum(): ?TpmPayloadRangeEnum
    {
        return TpmPayloadRangeEnum::tryFrom($this->payload_range);
    }
}
