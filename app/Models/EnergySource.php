<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EnergySource extends Model
{
    protected $fillable = ['name', 'code'];

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'energy_source_id');
    }
}
