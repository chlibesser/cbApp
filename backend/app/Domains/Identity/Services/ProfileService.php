<?php

namespace App\Domains\Identity\Services;

use App\Core\Auth\Models\Account;
use App\Core\Tenant\Models\Tenant;
use App\Domains\Identity\Models\Profile;

/**
 * ProfileService - Profile management service
 */
class ProfileService
{
    /**
     * Create a profile for an account in a tenant
     */
    public function createProfile(Account $account, Tenant $tenant, array $data): Profile
    {
        return Profile::create([
            'account_id' => $account->id,
            'tenant_id' => $tenant->id,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'display_name' => $data['display_name'] ?? null,
            'settings' => $data['settings'] ?? [],
            'is_active' => true,
        ]);
    }

    /**
     * Get all profiles for an account
     */
    public function getAccountProfiles(Account $account): \Illuminate\Database\Eloquent\Collection
    {
        return Profile::where('account_id', $account->id)
            ->with('tenant')
            ->get();
    }

    /**
     * Switch to a different profile
     */
    public function switchProfile(Account $account, Profile $profile): bool
    {
        if ($profile->account_id !== $account->id) {
            return false;
        }

        session(['current_profile' => $profile]);
        return true;
    }
}