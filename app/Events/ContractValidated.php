<?php

namespace App\Events;

use App\Models\Contract;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContractValidated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Contract $contract,
        public User $user
    ) {}
}
