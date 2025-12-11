<?php

namespace App\Infrastructure\Http\Controllers\Admin;

use App\Core\Tenant\Models\Tenant;
use App\Core\Tenant\Services\TenantService;
use App\Infrastructure\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * TenantController - Admin tenant management
 */
class TenantController extends Controller
{
    public function __construct(
        private TenantService $tenantService
    ) {}

    /**
     * List all tenants with ADT support (pagination, sorting, filtering)
     */
    public function index(Request $request)
    {
        $query = Tenant::withCount('profiles as users_count');

        // Search functionality
        if ($request->has('filter.search') && !empty($request->input('filter.search'))) {
            $searchTerm = $request->input('filter.search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'ILIKE', "%{$searchTerm}%")
                  ->orWhere('slug', 'ILIKE', "%{$searchTerm}%")
                  ->orWhere('description', 'ILIKE', "%{$searchTerm}%");
            });
        }

        // Sortierung
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        
        // Validierte Sortier-Felder für ADT
        $allowedSortFields = [
            'name', 'slug', 'description', 'is_personal', 'is_active', 
            'users_count', 'created_at', 'updated_at'
        ];
        
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        // ADT Filter
        if ($request->has('filter')) {
            foreach ($request->input('filter') as $field => $value) {
                if ($value === null || $value === '' || $field === 'search') {
                    continue;
                }

                switch ($field) {
                    case 'name':
                    case 'slug':
                    case 'description':
                        $query->where($field, 'ILIKE', "%{$value}%");
                        break;

                    case 'is_active':
                    case 'is_personal':
                        $query->where($field, filter_var($value, FILTER_VALIDATE_BOOLEAN));
                        break;

                    case 'users_count':
                        // For count filtering, we need to use HAVING
                        $query->having('users_count', '=', intval($value));
                        break;

                    case 'created_at':
                    case 'updated_at':
                        // Date filtering - supports different date formats
                        try {
                            $date = \Carbon\Carbon::parse($value)->format('Y-m-d');
                            $query->whereDate($field, $date);
                        } catch (\Exception $e) {
                            // Invalid date format, ignore filter
                        }
                        break;
                }
            }
        }

        // Paginierung
        $perPage = min($request->input('per_page', 25), 100); // Max 100 per page
        
        $tenants = $query->paginate($perPage);

        // Transform for ADT compatibility
        $tenants->transform(function ($tenant) {
            return [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'description' => $tenant->description,
                'is_personal' => $tenant->is_personal,
                'is_active' => $tenant->is_active,
                'users_count' => $tenant->users_count ?? 0,
                'created_at' => $tenant->created_at,
                'updated_at' => $tenant->updated_at,
                'settings' => $tenant->settings
            ];
        });

        return response()->json($tenants);
    }

    /**
     * Show a specific tenant
     */
    public function show(Tenant $tenant)
    {
        $tenant->loadCount('profiles as users_count');

        return response()->json([
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'description' => $tenant->description,
                'is_personal' => $tenant->is_personal,
                'is_active' => $tenant->is_active,
                'max_users' => $tenant->settings['max_users'] ?? null,
                'users_count' => $tenant->users_count,
                'current_users_count' => $tenant->users_count,
                'created_at' => $tenant->created_at,
                'updated_at' => $tenant->updated_at,
                'settings' => $tenant->settings
            ]
        ]);
    }

    /**
     * Create a new tenant
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:100', 'unique:tenants,slug', 'regex:/^[a-z0-9-]+$/'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_personal' => ['boolean'],
            'is_active' => ['boolean'],
            'settings' => ['array'],
        ]);

        // Auto-generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = \Str::slug($data['name']);
            
            // Ensure uniqueness
            $counter = 1;
            $originalSlug = $data['slug'];
            while (Tenant::where('slug', $data['slug'])->exists()) {
                $data['slug'] = $originalSlug . '-' . $counter++;
            }
        }

        // Default values
        $data['is_personal'] = $data['is_personal'] ?? false;
        $data['is_active'] = $data['is_active'] ?? true;
        $data['settings'] = $data['settings'] ?? [];

        $tenant = $this->tenantService->create($data);

        return response()->json([
            'message' => 'Tenant wurde erfolgreich erstellt',
            'tenant' => $tenant->load('profiles'),
        ], 201);
    }

    /**
     * Update a tenant
     */
    public function update(Request $request, Tenant $tenant)
    {
        $data = $request->validate([
            'name' => ['string', 'max:255'],
            'slug' => ['string', 'max:100', 'unique:tenants,slug,' . $tenant->id, 'regex:/^[a-z0-9-]+$/'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_personal' => ['boolean'],
            'is_active' => ['boolean'],
            'settings' => ['array'],
        ]);

        $tenant->update($data);
        $tenant->load(['profiles.account']);

        return response()->json([
            'message' => 'Tenant wurde erfolgreich aktualisiert',
            'tenant' => $tenant,
        ]);
    }

    /**
     * Delete a tenant
     */
    public function destroy(Tenant $tenant)
    {
        // Check if tenant has profiles (users)
        if ($tenant->profiles()->count() > 0) {
            return response()->json([
                'message' => 'Tenant kann nicht gelöscht werden: Es sind noch Benutzer zugewiesen.',
                'error' => 'TENANT_HAS_USERS'
            ], 409);
        }

        $tenant->delete();

        return response()->json([
            'message' => 'Tenant wurde erfolgreich gelöscht',
        ]);
    }

    /**
     * Get users for a specific tenant
     */
    public function users(Tenant $tenant)
    {
        $profiles = $tenant->profiles()
            ->with(['account'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'tenant' => $tenant,
            'users' => $profiles->map(function ($profile) {
                return [
                    'id' => $profile->id,
                    'first_name' => $profile->first_name,
                    'last_name' => $profile->last_name,
                    'email' => $profile->email,
                    'phone' => $profile->phone,
                    'system_role' => $profile->system_role,
                    'is_active' => $profile->is_active,
                    'created_at' => $profile->created_at,
                    'account' => [
                        'id' => $profile->account->id,
                        'username' => $profile->account->username,
                        'email' => $profile->account->email,
                        'is_active' => $profile->account->is_active,
                    ]
                ];
            })
        ]);
    }

    /**
     * Assign user to tenant
     */
    public function assignUser(Request $request, Tenant $tenant)
    {
        $data = $request->validate([
            'account_id' => ['required', 'uuid', 'exists:accounts,id'],
            'system_role' => ['required', 'string', 'in:tenant_admin,tenant_member'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        // Check if user already has a profile in this tenant
        if ($tenant->profiles()->where('account_id', $data['account_id'])->exists()) {
            return response()->json([
                'message' => 'Benutzer ist bereits diesem Tenant zugewiesen.',
                'error' => 'USER_ALREADY_ASSIGNED'
            ], 409);
        }

        // Create profile for this tenant
        $profile = $tenant->profiles()->create([
            'account_id' => $data['account_id'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'system_role' => $data['system_role'],
            'is_active' => true,
        ]);

        $profile->load('account');

        return response()->json([
            'message' => 'Benutzer wurde erfolgreich dem Tenant zugewiesen',
            'profile' => $profile,
        ], 201);
    }

    /**
     * Remove user from tenant
     */
    public function removeUser(Tenant $tenant, string $profileId)
    {
        $profile = $tenant->profiles()->where('id', $profileId)->first();

        if (!$profile) {
            return response()->json([
                'message' => 'Benutzer nicht in diesem Tenant gefunden.',
                'error' => 'PROFILE_NOT_FOUND'
            ], 404);
        }

        $profile->delete();

        return response()->json([
            'message' => 'Benutzer wurde erfolgreich vom Tenant entfernt',
        ]);
    }

    /**
     * Update user role in tenant
     */
    public function updateUserRole(Request $request, Tenant $tenant, string $profileId)
    {
        $data = $request->validate([
            'system_role' => ['required', 'string', 'in:tenant_admin,tenant_member'],
        ]);

        $profile = $tenant->profiles()->where('id', $profileId)->first();

        if (!$profile) {
            return response()->json([
                'message' => 'Benutzer nicht in diesem Tenant gefunden.',
                'error' => 'PROFILE_NOT_FOUND'
            ], 404);
        }

        $profile->update(['system_role' => $data['system_role']]);
        $profile->load('account');

        return response()->json([
            'message' => 'Benutzerrolle wurde erfolgreich aktualisiert',
            'profile' => $profile,
        ]);
    }
}