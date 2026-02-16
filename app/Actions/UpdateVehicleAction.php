<?php

namespace App\Actions;

use App\Models\Vehicle;

class UpdateVehicleAction
{
    public function execute(Vehicle $vehicle, array $validated): Vehicle
    {
        $clean = array_map(fn ($v) => $v === '' ? null : $v, $validated);
        $vehicle->update($clean);
        return $vehicle;
    }
}
