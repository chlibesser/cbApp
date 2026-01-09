<?php

namespace App\Infrastructure\Http\Controllers\Auth;

use App\Core\Auth\Services\AuthService;
use App\Infrastructure\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * LoginController - Handle authentication
 */
class LoginController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    /**
     * Handle login request with username or email
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'identifier' => ['required', 'string'], // username or email
            'password' => ['required', 'string'],
        ], [
            'identifier.required' => __('auth/login.validation.identifier_required'),
            'password.required' => __('auth/login.validation.password_required'),
        ]);

        $account = $this->authService->attempt($credentials);

        if (!$account) {
            return response()->json([
                'message' => __('auth/login.messages.failed'),
            ], 401);
        }

        // Create API token for SPA authentication
        $token = $this->authService->createToken($account);

        return response()->json([
            'message' => __('auth/login.messages.success'),
            'token' => $token,
            'account' => [
                'id' => $account->id,
                'username' => $account->username,
                'email' => $account->email,
                'is_active' => $account->is_active,
                'email_verified_at' => $account->email_verified_at,
            ],
        ]);
    }

    /**
     * Handle logout request
     */
    public function logout(Request $request)
    {
        $account = $request->user();

        if ($account) {
            $this->authService->logout($account);
        }

        return response()->json([
            'message' => __('auth/login.messages.logout_success'),
        ]);
    }

    /**
     * Get current authenticated account with tenant and profile data
     */
    public function me(Request $request)
    {
        $account = $request->user();

        if (!$account) {
            return response()->json([
                'message' => __('auth/login.messages.unauthenticated'),
            ], 401);
        }

        // Load tenants with roles and profiles
        $account->load(['tenants.roles', 'profiles']);

        $tenants = $account->tenants->map(function ($tenant) use ($account) {
            $role = $account->getRoleForTenant($tenant->id);
            return [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'is_personal' => $tenant->is_personal,
                'role' => $role ? [
                    'id' => $role->id,
                    'name' => $role->name,
                    'permissions' => $role->permissions->pluck('name'),
                ] : null,
            ];
        });

        // Get the first tenant as current (or could be based on user preference)
        $currentTenant = $tenants->first();

        // Get profile for current tenant
        $currentProfile = null;
        if ($currentTenant) {
            $profile = $account->profiles()->where('tenant_id', $currentTenant['id'])->first();
            if ($profile) {
                $currentProfile = [
                    'id' => $profile->id,
                    'display_name' => $profile->display_name,
                    'first_name' => $profile->first_name,
                    'last_name' => $profile->last_name,
                    'tenant_id' => $profile->tenant_id,
                ];
            }
        }

        return response()->json([
            'account' => [
                'id' => $account->id,
                'username' => $account->username,
                'email' => $account->email,
                'is_active' => $account->is_active,
                'preferred_locale' => $account->preferred_locale,
                'email_verified_at' => $account->email_verified_at,
                'system_role' => $account->system_role?->value,
                'system_role_label' => $account->system_role?->label(),
            ],
            'profile' => $currentProfile,
            'current_tenant' => $currentTenant,
            'tenants' => $tenants,
        ]);
    }
}