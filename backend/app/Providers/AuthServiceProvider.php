<?php

namespace App\Providers;

use App\Domains\Tenant\Models\Invitation;
use App\Domains\Tenant\Models\Profile;
use App\Domains\Tenant\Models\Tenant;
use App\Domains\Tenant\Policies\InvitationPolicy;
use App\Domains\Tenant\Policies\ProfilePolicy;
use App\Domains\Tenant\Policies\TenantPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     */
    protected $policies = [
        Tenant::class => TenantPolicy::class,
        Profile::class => ProfilePolicy::class,
        Invitation::class => InvitationPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
