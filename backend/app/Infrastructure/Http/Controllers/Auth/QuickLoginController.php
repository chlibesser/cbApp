<?php

namespace App\Infrastructure\Http\Controllers\Auth;

use App\Infrastructure\Http\Controllers\Controller;
use App\Core\Auth\Models\Account;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuickLoginController extends Controller
{
    /**
     * List available quick login accounts
     */
    public function index(): JsonResponse
    {
        if (app()->environment('production')) {
            return response()->json(['error' => 'Quick login only available in development'], 403);
        }

        $accounts = Account::with(['tenants.roles', 'profiles'])
            ->whereIn('username', ['max', 'admin', 'buchhalter', 'lese', 'user'])
            ->get()
            ->map(function ($account) {
                return [
                    'id' => $account->id,
                    'username' => $account->username,
                    'email' => $account->email,
                    'tenants' => $account->tenants->map(function ($tenant) use ($account) {
                        $role = $account->getRoleForTenant($tenant->id);
                        return [
                            'id' => $tenant->id,
                            'name' => $tenant->name,
                            'slug' => $tenant->slug,
                            'is_personal' => $tenant->is_personal,
                            'role' => $role ? [
                                'id' => $role->id,
                                'name' => $role->name,
                                'description' => $role->description,
                            ] : null,
                        ];
                    }),
                ];
            });

        return response()->json([
            'accounts' => $accounts,
            'environment' => app()->environment(),
        ]);
    }

    /**
     * Quick login with specified account
     */
    public function login(Request $request): JsonResponse
    {
        if (app()->environment('production')) {
            return response()->json(['error' => 'Quick login only available in development'], 403);
        }

        $request->validate([
            'username' => 'required|string',
            'tenant_id' => 'nullable|exists:tenants,id',
        ]);

        $account = Account::where('username', $request->username)
            ->whereIn('username', ['max', 'admin', 'buchhalter', 'lese', 'user'])
            ->first();

        if (!$account) {
            return response()->json(['error' => 'Account not found'], 404);
        }

        // Generate API token
        $token = $account->createToken('quick-login-' . $account->username)->plainTextToken;

        // Get tenant info if specified
        $currentTenant = null;
        if ($request->tenant_id) {
            $tenant = $account->tenants()->find($request->tenant_id);
            if ($tenant) {
                $role = $account->getRoleForTenant($tenant->id);
                $currentTenant = [
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
            }
        }

        return response()->json([
            'message' => 'Quick login successful',
            'account' => [
                'id' => $account->id,
                'username' => $account->username,
                'email' => $account->email,
            ],
            'current_tenant' => $currentTenant,
            'token' => $token,
        ]);
    }
}
