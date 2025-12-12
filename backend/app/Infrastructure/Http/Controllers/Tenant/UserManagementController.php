<?php

namespace App\Infrastructure\Http\Controllers\Tenant;

use App\Core\Auth\Models\Account;
use App\Domains\Tenant\Enums\SystemRole;
use App\Domains\Tenant\Models\Tenant;
use App\Domains\Tenant\Services\TenantUserService;
use App\Infrastructure\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserManagementController extends Controller
{
    public function __construct(
        private readonly TenantUserService $tenantUserService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenant = $this->getCurrentTenant($request);
        
        $users = $this->tenantUserService->getTenantUsers($tenant);
        
        // Transform für Frontend
        $userData = $users->map(function ($profile) {
            return [
                'id' => $profile->id,
                'full_name' => $profile->full_name,
                'first_name' => $profile->first_name,
                'last_name' => $profile->last_name,
                'email' => $profile->email,
                'phone' => $profile->phone,
                'system_role' => $profile->system_role->value,
                'system_role_label' => $profile->system_role->label(),
                'status' => $profile->status,
                'status_label' => $profile->status_label,
                'is_active' => $profile->is_active,
                'is_linked_to_account' => $profile->isLinkedToAccount(),
                'last_login_at' => $profile->account?->last_login_at,
                'created_at' => $profile->created_at,
                'updated_at' => $profile->updated_at,
            ];
        });

        return response()->json([
            'users' => $userData,
            'meta' => [
                'total' => $userData->count(),
                'active' => $userData->where('is_active', true)->count(),
                'invited' => $userData->where('status', 'invited')->count(),
            ]
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('inviteUsers', $this->getCurrentTenant($request));

        $validated = $request->validate([
            'first_name' => 'required|string|min:2|max:100',
            'last_name' => 'required|string|min:2|max:100',
            'email' => 'required|email:rfc,dns|max:255',
            'phone' => 'nullable|string|regex:/^[+]?[0-9\s\-()]+$/|max:50',
            'system_role' => [
                'required',
                Rule::enum(SystemRole::class)->except([SystemRole::GLOBAL_ADMIN])
            ],
        ], [
            'first_name.required' => 'Vorname ist erforderlich',
            'first_name.min' => 'Vorname muss mindestens 2 Zeichen lang sein',
            'last_name.required' => 'Nachname ist erforderlich',
            'last_name.min' => 'Nachname muss mindestens 2 Zeichen lang sein',
            'email.required' => 'E-Mail ist erforderlich',
            'email.email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein',
            'system_role.required' => 'System-Rolle ist erforderlich',
            'phone.regex' => 'Bitte geben Sie eine gültige Telefonnummer ein',
        ]);

        try {
            $tenant = $this->getCurrentTenant($request);
            $invitedBy = $request->user();

            $invitation = $this->tenantUserService->inviteUser(
                $tenant,
                $invitedBy,
                $validated
            );

            return response()->json([
                'message' => 'Benutzer wurde erfolgreich eingeladen',
                'invitation' => [
                    'id' => $invitation->id,
                    'email' => $invitation->email,
                    'status' => $invitation->status->label(),
                    'expires_at' => $invitation->expires_at,
                ],
                'profile' => [
                    'id' => $invitation->profile->id,
                    'full_name' => $invitation->profile->full_name,
                    'email' => $invitation->profile->email,
                    'system_role' => $invitation->profile->system_role->label(),
                ]
            ], 201);
            
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validierungsfehler',
                'errors' => $e->errors()
            ], 422);
        }
    }

    public function show(Request $request, string $profileId): JsonResponse
    {
        $tenant = $this->getCurrentTenant($request);
        
        $profile = $tenant->profiles()
            ->with(['account'])
            ->findOrFail($profileId);

        return response()->json([
            'user' => [
                'id' => $profile->id,
                'full_name' => $profile->full_name,
                'first_name' => $profile->first_name,
                'last_name' => $profile->last_name,
                'email' => $profile->email,
                'phone' => $profile->phone,
                'system_role' => $profile->system_role->value,
                'system_role_label' => $profile->system_role->label(),
                'status' => $profile->status,
                'status_label' => $profile->status_label,
                'is_active' => $profile->is_active,
                'is_linked_to_account' => $profile->isLinkedToAccount(),
                'account' => $profile->account ? [
                    'id' => $profile->account->id,
                    'email' => $profile->account->email,
                    'last_login_at' => $profile->account->last_login_at,
                ] : null,
                'created_at' => $profile->created_at,
                'updated_at' => $profile->updated_at,
            ]
        ]);
    }

    public function updateRole(Request $request, string $profileId): JsonResponse
    {
        $tenant = $this->getCurrentTenant($request);
        $profile = $tenant->profiles()->findOrFail($profileId);
        $this->authorize('updateRole', $profile);

        $validated = $request->validate([
            'system_role' => [
                'required',
                Rule::enum(SystemRole::class)->except([SystemRole::GLOBAL_ADMIN])
            ],
        ], [
            'system_role.required' => 'System-Rolle ist erforderlich',
        ]);

        try {
            $updatedBy = $request->user();

            $newRole = SystemRole::from($validated['system_role']);
            
            $updatedProfile = $this->tenantUserService->updateUserRole(
                $profile,
                $newRole,
                $updatedBy
            );

            return response()->json([
                'message' => 'Benutzer-Rolle wurde erfolgreich aktualisiert',
                'user' => [
                    'id' => $updatedProfile->id,
                    'full_name' => $updatedProfile->full_name,
                    'system_role' => $updatedProfile->system_role->value,
                    'system_role_label' => $updatedProfile->system_role->label(),
                ]
            ]);
            
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Fehler beim Aktualisieren der Rolle',
                'errors' => $e->errors()
            ], 422);
        }
    }

    public function deactivate(Request $request, string $profileId): JsonResponse
    {
        $tenant = $this->getCurrentTenant($request);
        $profile = $tenant->profiles()->findOrFail($profileId);
        $this->authorize('deactivate', $profile);

        try {
            $deactivatedBy = $request->user();

            $updatedProfile = $this->tenantUserService->deactivateUser($profile, $deactivatedBy);

            return response()->json([
                'message' => 'Benutzer wurde erfolgreich deaktiviert',
                'user' => [
                    'id' => $updatedProfile->id,
                    'full_name' => $updatedProfile->full_name,
                    'is_active' => $updatedProfile->is_active,
                    'status' => $updatedProfile->status,
                    'status_label' => $updatedProfile->status_label,
                ]
            ]);
            
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Fehler beim Deaktivieren des Benutzers',
                'errors' => $e->errors()
            ], 422);
        }
    }

    public function activate(Request $request, string $profileId): JsonResponse
    {
        $tenant = $this->getCurrentTenant($request);
        $profile = $tenant->profiles()->findOrFail($profileId);
        $this->authorize('activate', $profile);

        try {
            $activatedBy = $request->user();

            $updatedProfile = $this->tenantUserService->activateUser($profile, $activatedBy);

            return response()->json([
                'message' => 'Benutzer wurde erfolgreich aktiviert',
                'user' => [
                    'id' => $updatedProfile->id,
                    'full_name' => $updatedProfile->full_name,
                    'is_active' => $updatedProfile->is_active,
                    'status' => $updatedProfile->status,
                    'status_label' => $updatedProfile->status_label,
                ]
            ]);
            
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Fehler beim Aktivieren des Benutzers',
                'errors' => $e->errors()
            ], 422);
        }
    }

    public function destroy(Request $request, string $profileId): JsonResponse
    {
        $tenant = $this->getCurrentTenant($request);
        $profile = $tenant->profiles()->findOrFail($profileId);
        $this->authorize('delete', $profile);

        $this->tenantUserService->removeUser($profile);

        return response()->json([
            'message' => 'Benutzer wurde erfolgreich entfernt'
        ]);
    }

    public function resendInvitation(Request $request, string $profileId): JsonResponse
    {
        $tenant = $this->getCurrentTenant($request);
        $profile = $tenant->profiles()->findOrFail($profileId);
        $this->authorize('resendInvitation', $profile);

        try {
            $invitedBy = $request->user();

            $invitation = $this->tenantUserService->resendInvitation($profile, $invitedBy);

            return response()->json([
                'message' => 'Einladung wurde erfolgreich erneut versendet',
                'invitation' => [
                    'id' => $invitation->id,
                    'email' => $invitation->email,
                    'status' => $invitation->status->label(),
                    'expires_at' => $invitation->expires_at,
                ]
            ]);
            
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Fehler beim Versenden der Einladung',
                'errors' => $e->errors()
            ], 422);
        }
    }

    private function getCurrentTenant(Request $request): Tenant
    {
        $tenantId = $request->header('X-Tenant-ID');
        
        if (!$tenantId) {
            abort(400, 'X-Tenant-ID header ist erforderlich');
        }

        return Tenant::findOrFail($tenantId);
    }
}