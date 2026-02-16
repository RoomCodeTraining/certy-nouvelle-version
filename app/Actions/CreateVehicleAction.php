<?php

namespace App\Actions;

use App\Models\Vehicle;

class CreateVehicleAction
{
    public function execute(array $validated): Vehicle
    {
        $clean = array_map(fn ($v) => $v === '' ? null : $v, $validated);
        return Vehicle::create($clean);
    }
}
