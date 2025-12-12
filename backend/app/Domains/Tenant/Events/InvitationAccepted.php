<?php

namespace App\Domains\Tenant\Events;

use App\Core\Auth\Models\Account;
use App\Domains\Tenant\Models\Invitation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvitationAccepted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Invitation $invitation,
        public ?Account $account = null
    ) {}
}