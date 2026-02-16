<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class OrganizationCompanyConfig extends Model
{
    protected $table = 'organization_company_configs';

    protected $fillable = [
        'name',
        'code',
        'commission',
        'policy_number_identifier',
    ];

    protected function casts(): array
    {
        return [
            'commission' => 'decimal:2',
        ];
    }
}
