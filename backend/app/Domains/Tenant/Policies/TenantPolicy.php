<?php

namespace App\Domains\Tenant\Policies;

use App\Core\Auth\Models\Account;
use App\Core\Shared\Traits\SkipsAuthorizationInDevelopment;
use App\Domains\Tenant\Models\Profile;
use App\Domains\Tenant\Models\Tenant;
use Illuminate\Auth\Access\HandlesAuthorization;

class TenantPolicy
{
    use HandlesAuthorization, SkipsAuthorizationInDevelopment;

    /**
     * Prüft ob der Account Benutzer in diesem Tenant einladen kann
     */
    public function inviteUsers(Account $account, Tenant $tenant): bool
    {
        return $this->authorize(function () use ($account, $tenant) {
            $profile = $this->getTenantProfile($account, $tenant);
            
            if (!$profile) {
                return false;
            }

            // Nur Tenant-Admins können Benutzer einladen
            return $profile->canManageUsers();
        });
    }

    /**
     * Prüft ob der Account Benutzer in diesem Tenant verwalten kann
     */
    public function manageUsers(Account $account, Tenant $tenant): bool
    {
        return $this->authorize(function () use ($account, $tenant) {
            $profile = $this->getTenantProfile($account, $tenant);
            
            if (!$profile) {
                return false;
            }

            // Nur Tenant-Admins können Benutzer verwalten
            return $profile->canManageUsers();
        });
    }

    /**
     * Prüft ob der Account Benutzer in diesem Tenant anzeigen kann
     */
    public function viewUsers(Account $account, Tenant $tenant): bool
    {
        return $this->authorize(function () use ($account, $tenant) {
            $profile = $this->getTenantProfile($account, $tenant);
            
            if (!$profile) {
                return false;
            }

            // Alle aktiven Profile können Benutzer anzeigen
            return $profile->isActive();
        });
    }

    /**
     * Prüft ob der Account Rollen in diesem Tenant verwalten kann
     */
    public function manageRoles(Account $account, Tenant $tenant): bool
    {
        return $this->authorize(function () use ($account, $tenant) {
            $profile = $this->getTenantProfile($account, $tenant);
            
            if (!$profile) {
                return false;
            }

            // Nur Tenant-Admins können Rollen verwalten
            return $profile->canManageUsers();
        });
    }

    /**
     * Prüft ob der Account Benutzer deaktivieren kann
     */
    public function deactivateUsers(Account $account, Tenant $tenant): bool
    {
        return $this->manageUsers($account, $tenant);
    }

    /**
     * Prüft ob der Account auf diesen Tenant zugreifen kann
     */
    public function access(Account $account, Tenant $tenant): bool
    {
        return $this->authorize(function () use ($account, $tenant) {
            $profile = $this->getTenantProfile($account, $tenant);
            
            if (!$profile) {
                return false;
            }

            // Muss aktives Profile haben
            return $profile->isActive() && $tenant->isActive();
        });
    }

    /**
     * Hilfsmethode um das Profile des Accounts für den Tenant zu finden
     */
    private function getTenantProfile(Account $account, Tenant $tenant): ?Profile
    {
        return $tenant->profiles()
            ->where('account_id', $account->id)
            ->first();
    }
}