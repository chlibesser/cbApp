<?php

namespace App\Domains\Tenant\Policies;

use App\Core\Auth\Models\Account;
use App\Core\Shared\Traits\SkipsAuthorizationInDevelopment;
use App\Domains\Tenant\Enums\SystemRole;
use App\Domains\Tenant\Models\Profile;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProfilePolicy
{
    use HandlesAuthorization, SkipsAuthorizationInDevelopment;

    /**
     * Prüft ob der Account Profile anzeigen kann
     */
    public function view(Account $account, Profile $profile): bool
    {
        $currentProfile = $this->getCurrentProfile($account, $profile->tenant);
        
        if (!$currentProfile) {
            return false;
        }

        // Kann eigenes Profile anzeigen oder ist Tenant-Admin
        return $currentProfile->id === $profile->id || $currentProfile->canManageUsers();
    }

    /**
     * Prüft ob der Account Profile bearbeiten kann
     */
    public function update(Account $account, Profile $profile): bool
    {
        $currentProfile = $this->getCurrentProfile($account, $profile->tenant);
        
        if (!$currentProfile) {
            return false;
        }

        // Kann eigenes Profile bearbeiten oder ist Tenant-Admin
        return $currentProfile->id === $profile->id || $currentProfile->canManageUsers();
    }

    /**
     * Prüft ob der Account die Rolle eines Profiles ändern kann
     */
    public function updateRole(Account $account, Profile $profile): bool
    {
        $currentProfile = $this->getCurrentProfile($account, $profile->tenant);
        
        if (!$currentProfile) {
            return false;
        }

        // Kann nicht eigene Rolle ändern
        if ($currentProfile->id === $profile->id) {
            return false;
        }

        // Nur Tenant-Admins können Rollen ändern
        if (!$currentProfile->canManageUsers()) {
            return false;
        }

        // Kann keine anderen Tenant-Admins zu Global-Admins machen
        if ($profile->system_role === SystemRole::GLOBAL_ADMIN) {
            return false;
        }

        return true;
    }

    /**
     * Prüft ob der Account Profile deaktivieren kann
     */
    public function deactivate(Account $account, Profile $profile): bool
    {
        $currentProfile = $this->getCurrentProfile($account, $profile->tenant);
        
        if (!$currentProfile) {
            return false;
        }

        // Kann sich nicht selbst deaktivieren
        if ($currentProfile->id === $profile->id) {
            return false;
        }

        // Nur Tenant-Admins können deaktivieren
        return $currentProfile->canManageUsers();
    }

    /**
     * Prüft ob der Account Profile aktivieren kann
     */
    public function activate(Account $account, Profile $profile): bool
    {
        $currentProfile = $this->getCurrentProfile($account, $profile->tenant);
        
        if (!$currentProfile) {
            return false;
        }

        // Nur Tenant-Admins können aktivieren
        return $currentProfile->canManageUsers();
    }

    /**
     * Prüft ob der Account Profile löschen kann
     */
    public function delete(Account $account, Profile $profile): bool
    {
        $currentProfile = $this->getCurrentProfile($account, $profile->tenant);
        
        if (!$currentProfile) {
            return false;
        }

        // Kann sich nicht selbst löschen
        if ($currentProfile->id === $profile->id) {
            return false;
        }

        // Nur Tenant-Admins können löschen
        return $currentProfile->canManageUsers();
    }

    /**
     * Prüft ob der Account Einladungen für Profile versenden kann
     */
    public function resendInvitation(Account $account, Profile $profile): bool
    {
        $currentProfile = $this->getCurrentProfile($account, $profile->tenant);
        
        if (!$currentProfile) {
            return false;
        }

        // Profile darf noch nicht mit Account verknüpft sein
        if ($profile->isLinkedToAccount()) {
            return false;
        }

        // Nur Tenant-Admins können Einladungen versenden
        return $currentProfile->canManageUsers();
    }

    /**
     * Hilfsmethode um das aktuelle Profile des Accounts zu finden
     */
    private function getCurrentProfile(Account $account, $tenant): ?Profile
    {
        return $tenant->profiles()
            ->where('account_id', $account->id)
            ->where('is_active', true)
            ->first();
    }
}