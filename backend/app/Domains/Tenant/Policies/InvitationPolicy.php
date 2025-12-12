<?php

namespace App\Domains\Tenant\Policies;

use App\Core\Auth\Models\Account;
use App\Domains\Tenant\Models\Invitation;
use App\Domains\Tenant\Models\Profile;
use Illuminate\Auth\Access\HandlesAuthorization;

class InvitationPolicy
{
    use HandlesAuthorization;

    /**
     * Prüft ob der Account Einladungen anzeigen kann
     */
    public function view(Account $account, Invitation $invitation): bool
    {
        $currentProfile = $this->getCurrentProfile($account, $invitation->tenant);
        
        if (!$currentProfile) {
            return false;
        }

        // Nur Tenant-Admins können Einladungen anzeigen
        return $currentProfile->canManageUsers();
    }

    /**
     * Prüft ob der Account Einladungen erstellen kann
     */
    public function create(Account $account, $tenant): bool
    {
        $currentProfile = $this->getCurrentProfile($account, $tenant);
        
        if (!$currentProfile) {
            return false;
        }

        // Nur Tenant-Admins können Einladungen erstellen
        return $currentProfile->canManageUsers();
    }

    /**
     * Prüft ob der Account Einladungen stornieren kann
     */
    public function cancel(Account $account, Invitation $invitation): bool
    {
        $currentProfile = $this->getCurrentProfile($account, $invitation->tenant);
        
        if (!$currentProfile) {
            return false;
        }

        // Nur ausstehende Einladungen können storniert werden
        if (!$invitation->isPending()) {
            return false;
        }

        // Nur Tenant-Admins oder der Einladende können stornieren
        return $currentProfile->canManageUsers() || 
               $invitation->invited_by === $account->id;
    }

    /**
     * Prüft ob der Account Einladungen erneut versenden kann
     */
    public function resend(Account $account, Invitation $invitation): bool
    {
        $currentProfile = $this->getCurrentProfile($account, $invitation->tenant);
        
        if (!$currentProfile) {
            return false;
        }

        // Einladung darf noch nicht angenommen worden sein
        if ($invitation->isAccepted()) {
            return false;
        }

        // Profile darf noch nicht mit Account verknüpft sein
        if ($invitation->profile->isLinkedToAccount()) {
            return false;
        }

        // Nur Tenant-Admins können Einladungen erneut versenden
        return $currentProfile->canManageUsers();
    }

    /**
     * Prüft ob die Einladung angenommen werden kann (öffentlich)
     */
    public function accept(?Account $account, Invitation $invitation): bool
    {
        // Einladung muss gültig und ausstehend sein
        if (!$invitation->isPending()) {
            return false;
        }

        // Wenn Account bereitgestellt, muss Email übereinstimmen
        if ($account && $account->email !== $invitation->email) {
            return false;
        }

        return true;
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