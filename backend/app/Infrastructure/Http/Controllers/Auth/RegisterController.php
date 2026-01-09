<?php

namespace App\Infrastructure\Http\Controllers\Auth;

use App\Core\Auth\Enums\SystemRole;
use App\Core\Auth\Models\Account;
use App\Core\Tenant\Models\Tenant;
use App\Core\Tenant\Models\Role;
use App\Core\Shared\Models\Permission;
use App\Domains\Identity\Models\Profile;
use App\Infrastructure\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RegisterController extends Controller
{
    /**
     * Register a new user account with automatic tenant creation
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:accounts,email',
            'username' => 'nullable|string|min:3|max:50|unique:accounts,username|regex:/^[a-zA-Z0-9_.-]+$/',
            'password' => 'required|string|min:8|confirmed',
            'company_name' => 'nullable|string|max:255',
            'accept_terms' => 'required|accepted',
        ], [
            'email.unique' => __('auth/register.validation.email_unique'),
            'username.unique' => __('auth/register.validation.username_unique'),
            'username.regex' => __('auth/register.validation.username_regex'),
            'password.confirmed' => __('auth/register.validation.password_confirmed'),
            'accept_terms.accepted' => __('auth/register.validation.accept_terms_required'),
        ]);

        // Create tenant name: Company name or personal workspace
        $tenantName = $request->company_name ?? $request->first_name . "'s Workspace";
        $isPersonal = !$request->company_name;
        
        // Generate unique slug
        $baseSlug = Str::slug($tenantName);
        $slug = $baseSlug;
        $counter = 1;
        
        while (Tenant::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        // Create tenant first
        $tenant = Tenant::create([
            'name' => $tenantName,
            'slug' => $slug,
            'is_personal' => $isPersonal,
            'is_active' => true,
        ]);

        // Create roles for the new tenant
        $this->createRolesForTenant($tenant);

        // Generate username if not provided
        $username = $request->username;
        if (!$username) {
            // Generate from email: "max@test.com" -> "max"
            $emailLocal = explode('@', $request->email)[0];
            $baseUsername = Str::slug($emailLocal, '');
            
            // Ensure uniqueness
            $counter = 1;
            $username = $baseUsername;
            while (Account::where('username', $username)->exists()) {
                $username = $baseUsername . $counter;
                $counter++;
            }
        }

        // Create account
        $account = Account::create([
            'username' => $username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_active' => true,
            'system_role' => $isPersonal ? SystemRole::MEMBER : SystemRole::TENANT_ADMIN,
        ]);

        // Get Owner role for the new tenant
        $ownerRole = Role::where('tenant_id', $tenant->id)->where('name', 'Owner')->first();

        // Add account to tenant with Owner role
        $account->tenants()->attach($tenant->id, ['role_id' => $ownerRole->id]);

        // Create profile
        $displayName = $request->first_name . ' ' . $request->last_name;
        Profile::create([
            'account_id' => $account->id,
            'tenant_id' => $tenant->id,
            'display_name' => $displayName,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
        ]);

        // Generate API token
        $token = $account->createToken('registration-' . $account->username)->plainTextToken;

        return response()->json([
            'message' => __('auth/register.success'),
            'account' => [
                'id' => $account->id,
                'username' => $account->username,
                'email' => $account->email,
                'system_role' => $account->system_role?->value,
                'system_role_label' => $account->system_role?->label(),
            ],
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'is_personal' => $tenant->is_personal,
            ],
            'token' => $token,
        ], 201);
    }

    /**
     * Check if username is available
     */
    public function checkUsername(Request $request): JsonResponse
    {
        $request->validate([
            'username' => 'required|string|min:3|max:50|regex:/^[a-zA-Z0-9_.-]+$/',
        ]);

        $exists = Account::where('username', $request->username)->exists();

        return response()->json([
            'available' => !$exists,
            'message' => $exists
                ? __('auth/register.username_taken')
                : __('auth/register.username_available')
        ]);
    }

    /**
     * Check if email is available  
     */
    public function checkEmail(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $exists = Account::where('email', $request->email)->exists();

        return response()->json([
            'available' => !$exists,
            'message' => $exists
                ? __('auth/register.email_taken')
                : __('auth/register.email_available')
        ]);
    }

    /**
     * Create standard roles for a new tenant
     */
    private function createRolesForTenant(Tenant $tenant): void
    {
        // Owner Role (für Personal Tenants oder Firmen-Owner)
        $ownerRole = Role::create([
            'tenant_id' => $tenant->id,
            'name' => __('auth/register.roles.owner.name'),
            'description' => __('auth/register.roles.owner.description'),
            'is_system' => true,
        ]);

        // Admin Role
        $adminRole = Role::create([
            'tenant_id' => $tenant->id,
            'name' => __('auth/register.roles.admin.name'),
            'description' => __('auth/register.roles.admin.description'),
            'is_system' => true,
        ]);

        // User Role
        $userRole = Role::create([
            'tenant_id' => $tenant->id,
            'name' => __('auth/register.roles.user.name'),
            'description' => __('auth/register.roles.user.description'),
            'is_system' => true,
        ]);

        // Assign permissions
        $this->assignPermissions($ownerRole, Permission::all()->pluck('id')->toArray());
        $this->assignPermissions($adminRole, Permission::whereNotIn('resource', ['roles', 'settings'])->pluck('id')->toArray());
        $this->assignPermissions($userRole, Permission::whereIn('name', ['dashboard.view'])->pluck('id')->toArray());
    }

    /**
     * Assign permissions to a role
     */
    private function assignPermissions(Role $role, array $permissionIds): void
    {
        $role->permissions()->sync($permissionIds);
    }
}