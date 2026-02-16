<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleUsage extends Model
{
    protected $fillable = ['name', 'code'];

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'vehicle_usage_id');
    }
}
