<?php

namespace App\Actions;

use App\Models\Client;
use App\Models\Profession;

class UpdateClientAction
{
    public function execute(Client $client, array $validated): Client
    {
        $profession = Profession::firstOrCreate(['name' => trim($validated['profession'])]);
        $validated['profession_id'] = $profession->id;
        unset($validated['profession']);

        $client->update($validated);

        return $client;
    }
}
