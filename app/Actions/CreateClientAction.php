<?php

namespace App\Actions;

use App\Models\Client;
use App\Models\Profession;
use App\Models\User;
use Illuminate\Support\Facades\Event;

class CreateClientAction
{
    public function execute(User $user, array $validated): Client
    {
        $organization = $user->currentOrganization();
        if (! $organization) {
            abort(403, 'Aucune organisation associée.');
        }

        $profession = Profession::firstOrCreate(['name' => trim($validated['profession'])]);
        $validated['profession_id'] = $profession->id;
        unset($validated['profession']);
        $validated['organization_id'] = $organization->id;
        $validated['owner_id'] = $user->id;

        $client = Client::create($validated);

        Event::dispatch(new \App\Events\ClientCreated($client, $user));

        return $client;
    }
}
