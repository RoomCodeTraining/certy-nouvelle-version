<?php

namespace App\Listeners;

use App\Events\ClientCreated;
use Illuminate\Support\Facades\Log;

class LogClientCreated
{
    public function handle(ClientCreated $event): void
    {
        Log::info('Client créé', [
            'client_id' => $event->client->id,
            'user_id' => $event->user->id,
        ]);
    }
}
