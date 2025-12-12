<?php

namespace App\Domains\Tenant\Events;

use App\Core\Auth\Models\Account;
use App\Domains\Tenant\Enums\SystemRole;
use App\Domains\Tenant\Models\Profile;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserRoleChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Profile $profile,
        public SystemRole $oldRole,
        public SystemRole $newRole,
        public Account $updatedBy
    ) {}
}