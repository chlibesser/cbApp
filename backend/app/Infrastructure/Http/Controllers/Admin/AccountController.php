<?php

namespace App\Infrastructure\Http\Controllers\Admin;

use App\Core\Auth\Models\Account;
use App\Core\Auth\Services\AuthService;
use App\Infrastructure\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * AccountController - Admin account management
 */
class AccountController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    /**
     * List all accounts with ADT support (pagination, sorting, filtering)
     */
    public function index(Request $request)
    {
        $query = Account::with(['profiles.tenant'])
                      ->withCount('profiles as tenants_count');

        // Search functionality
        if ($request->has('filter.search') && !empty($request->input('filter.search'))) {
            $searchTerm = $request->input('filter.search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('username', 'ILIKE', "%{$searchTerm}%")
                  ->orWhere('email', 'ILIKE', "%{$searchTerm}%");
            });
        }

        // Sortierung
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        
        // Validierte Sortier-Felder für ADT
        $allowedSortFields = [
            'username', 'email', 'email_verified_at', 'system_role', 'is_active', 
            'tenants_count', 'last_login_at', 'created_at', 'updated_at'
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
                    case 'username':
                    case 'email':
                        $query->where($field, 'ILIKE', "%{$value}%");
                        break;

                    case 'system_role':
                        $query->where($field, $value);
                        break;

                    case 'is_active':
                        $query->where($field, filter_var($value, FILTER_VALIDATE_BOOLEAN));
                        break;

                    case 'email_verified_at':
                        // Boolean filter for email verification
                        if (filter_var($value, FILTER_VALIDATE_BOOLEAN)) {
                            $query->whereNotNull('email_verified_at');
                        } else {
                            $query->whereNull('email_verified_at');
                        }
                        break;

                    case 'tenants_count':
                        // For count filtering, we need to use HAVING
                        $query->having('tenants_count', '=', intval($value));
                        break;

                    case 'created_at':
                    case 'updated_at':
                    case 'last_login_at':
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
        
        $accounts = $query->paginate($perPage);

        // Transform for ADT compatibility
        $accounts->transform(function ($account) {
            return [
                'id' => $account->id,
                'username' => $account->username,
                'email' => $account->email,
                'email_verified_at' => $account->email_verified_at,
                'system_role' => $account->system_role,
                'is_active' => $account->is_active,
                'tenants_count' => $account->tenants_count ?? 0,
                'last_login_at' => $account->last_login_at,
                'created_at' => $account->created_at,
                'updated_at' => $account->updated_at
            ];
        });

        return response()->json($accounts);
    }

    /**
     * Show a specific account
     */
    public function show(Account $account)
    {
        $account->load(['profiles.tenant']);

        return response()->json($account);
    }

    /**
     * Create a new account
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'unique:accounts', 'max:255'],
            'email' => ['required', 'email', 'unique:accounts'],
            'password' => ['required', 'min:8'],
        ]);

        $account = $this->authService->register($data);

        return response()->json([
            'message' => 'Account created successfully',
            'account' => $account,
        ], 201);
    }

    /**
     * Update an account
     */
    public function update(Request $request, Account $account)
    {
        $data = $request->validate([
            'username' => ['string', 'unique:accounts,username,' . $account->id, 'max:255'],
            'email' => ['email', 'unique:accounts,email,' . $account->id],
        ]);

        $account->update($data);

        return response()->json([
            'message' => 'Account updated successfully',
            'account' => $account,
        ]);
    }

    /**
     * Delete an account
     */
    public function destroy(Account $account)
    {
        $account->delete();

        return response()->json([
            'message' => 'Account deleted successfully',
        ]);
    }
}