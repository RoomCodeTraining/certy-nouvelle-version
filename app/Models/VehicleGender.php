<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleGender extends Model
{
    protected $fillable = ['name', 'code'];

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'vehicle_gender_id');
    }
}
