<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OptionalGuarantee extends Model
{
    protected $fillable = [
        'code',
        'label',
        'rate',
        'base',
        'enabled',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:3',
            'enabled' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}

