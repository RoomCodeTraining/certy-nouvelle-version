<?php

namespace App\Events;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClientCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Client $client,
        public User $user
    ) {}
}
