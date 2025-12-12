<?php

namespace App\Domains\Tenant\Services;

use App\Core\Auth\Models\Account;
use App\Domains\Tenant\Enums\InvitationStatus;
use App\Domains\Tenant\Enums\SystemRole;
use App\Domains\Tenant\Events\InvitationAccepted;
use App\Domains\Tenant\Events\UserInvited;
use App\Domains\Tenant\Events\UserRoleChanged;
use App\Domains\Tenant\Events\UserStatusChanged;
use App\Domains\Tenant\Models\Invitation;
use App\Domains\Tenant\Models\Profile;
use App\Domains\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TenantUserService
{
    public function inviteUser(
        Tenant $tenant,
        Account $invitedBy,
        array $userData
    ): Invitation {
        return DB::transaction(function () use ($tenant, $invitedBy, $userData) {
            // Prüfung: Email bereits im Tenant vorhanden?
            $existingProfile = $this->getProfileByEmail($tenant, $userData['email']);
            
            if ($existingProfile) {
                throw ValidationException::withMessages([
                    'email' => ['Diese E-Mail-Adresse hat bereits ein Profil in diesem Tenant.']
                ]);
            }

            // Erstelle Profile (inaktiv)
            $profile = $this->createProfile($tenant, $userData);

            // Erstelle Einladung
            $invitation = $this->createInvitation($tenant, $profile, $invitedBy);

            // Event für Email-Versand
            event(new UserInvited($invitation));

            return $invitation->load(['tenant', 'profile', 'invitedBy']);
        });
    }

    public function acceptInvitation(string $token, ?Account $account = null): Profile
    {
        return DB::transaction(function () use ($token, $account) {
            $invitation = $this->getInvitationByToken($token);

            if (!$invitation) {
                throw ValidationException::withMessages([
                    'token' => ['Einladung nicht gefunden.']
                ]);
            }

            if (!$invitation->isPending()) {
                throw ValidationException::withMessages([
                    'token' => ['Diese Einladung ist nicht mehr gültig.']
                ]);
            }

            // Wenn Account bereitgestellt, verknüpfe mit Profile
            if ($account) {
                $this->linkProfileToAccount($invitation->profile, $account);
            }

            // Aktiviere Profile
            $this->activateProfile($invitation->profile);

            // Akzeptiere Einladung
            $invitation->accept();

            // Event für weitere Aktionen
            event(new InvitationAccepted($invitation, $account));

            return $invitation->profile->load(['account', 'tenant']);
        });
    }

    public function resendInvitation(Profile $profile, Account $invitedBy): Invitation
    {
        if ($profile->isLinkedToAccount()) {
            throw ValidationException::withMessages([
                'profile' => ['Dieses Profil ist bereits mit einem Account verbunden.']
            ]);
        }

        if (!$profile->email) {
            throw ValidationException::withMessages([
                'profile' => ['Dieses Profil hat keine E-Mail-Adresse.']
            ]);
        }

        return DB::transaction(function () use ($profile, $invitedBy) {
            // Storniere alte Einladungen
            $this->cancelPendingInvitations($profile);

            // Erstelle neue Einladung
            $invitation = $this->createInvitation($profile->tenant, $profile, $invitedBy);

            // Event für Email-Versand
            event(new UserInvited($invitation));

            return $invitation->load(['tenant', 'profile', 'invitedBy']);
        });
    }

    public function cancelInvitation(Invitation $invitation): void
    {
        if (!$invitation->isPending()) {
            throw ValidationException::withMessages([
                'invitation' => ['Nur ausstehende Einladungen können storniert werden.']
            ]);
        }

        $invitation->cancel();
    }

    public function updateUserRole(Profile $profile, SystemRole $newRole, Account $updatedBy): Profile
    {
        $oldRole = $profile->system_role;

        $profile->update([
            'system_role' => $newRole
        ]);

        // Event für Audit-Log
        event(new UserRoleChanged($profile, $oldRole, $newRole, $updatedBy));

        return $profile->refresh();
    }

    public function deactivateUser(Profile $profile, Account $deactivatedBy): Profile
    {
        if (!$profile->is_active) {
            throw ValidationException::withMessages([
                'profile' => ['Benutzer ist bereits deaktiviert.']
            ]);
        }

        $profile->update(['is_active' => false]);

        // Event für Audit-Log
        event(new UserStatusChanged($profile, true, false, $deactivatedBy));

        return $profile->refresh();
    }

    public function activateUser(Profile $profile, Account $activatedBy): Profile
    {
        if ($profile->is_active) {
            throw ValidationException::withMessages([
                'profile' => ['Benutzer ist bereits aktiv.']
            ]);
        }

        $profile->update(['is_active' => true]);

        // Event für Audit-Log
        event(new UserStatusChanged($profile, false, true, $activatedBy));

        return $profile->refresh();
    }

    public function removeUser(Profile $profile): void
    {
        // Soft delete
        $profile->delete();

        // Storniere ausstehende Einladungen
        $this->cancelPendingInvitations($profile);
    }

    public function getTenantUsers(Tenant $tenant): Collection
    {
        return $tenant->profiles()
            ->with(['account'])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    public function getPendingInvitations(Tenant $tenant): Collection
    {
        return $tenant->pendingInvitations()
            ->with(['profile', 'invitedBy'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    private function createProfile(Tenant $tenant, array $userData): Profile
    {
        return Profile::create([
            'tenant_id' => $tenant->id,
            'first_name' => $userData['first_name'],
            'last_name' => $userData['last_name'],
            'email' => $userData['email'],
            'phone' => $userData['phone'] ?? null,
            'system_role' => $userData['system_role'] ?? SystemRole::TENANT_MEMBER,
            'is_active' => false,
        ]);
    }

    private function createInvitation(Tenant $tenant, Profile $profile, Account $invitedBy): Invitation
    {
        return Invitation::create([
            'tenant_id' => $tenant->id,
            'profile_id' => $profile->id,
            'email' => $profile->email,
            'invited_by' => $invitedBy->id,
        ]);
    }

    private function linkProfileToAccount(Profile $profile, Account $account): void
    {
        if ($profile->email !== $account->email) {
            throw ValidationException::withMessages([
                'account' => ['Die Account E-Mail stimmt nicht mit der Einladungs-E-Mail überein.']
            ]);
        }

        $profile->update(['account_id' => $account->id]);
    }

    private function activateProfile(Profile $profile): void
    {
        $profile->update(['is_active' => true]);
    }

    private function getProfileByEmail(Tenant $tenant, string $email): ?Profile
    {
        return $tenant->profiles()
            ->where('email', $email)
            ->first();
    }

    private function getInvitationByToken(string $token): ?Invitation
    {
        return Invitation::where('token', $token)
            ->with(['tenant', 'profile'])
            ->first();
    }

    private function cancelPendingInvitations(Profile $profile): void
    {
        Invitation::where('profile_id', $profile->id)
            ->where('status', InvitationStatus::PENDING)
            ->update(['status' => InvitationStatus::CANCELLED]);
    }
}