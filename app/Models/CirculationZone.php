<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CirculationZone extends Model
{
    protected $fillable = ['name', 'code'];

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'circulation_zone_id');
    }
}
