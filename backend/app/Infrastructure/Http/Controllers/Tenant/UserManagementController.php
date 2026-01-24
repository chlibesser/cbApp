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

        // Query-Parameter
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search');
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $filters = $request->input('filters');

        // Basis-Query mit Eager Loading
        $query = $tenant->profiles()->with(['account']);

        // Suche
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'ilike', "%{$search}%")
                  ->orWhere('last_name', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%")
                  ->orWhere('phone', 'ilike', "%{$search}%");
            });
        }

        // Spalten-Filter anwenden
        if ($filters) {
            $columnFilters = json_decode($filters, true);
            if (is_array($columnFilters)) {
                foreach ($columnFilters as $filter) {
                    $query = $this->applyColumnFilter($query, $filter);
                }
            }
        }

        // Sortierung
        $allowedSortFields = ['first_name', 'last_name', 'email', 'system_role', 'is_active', 'created_at', 'updated_at'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
        }

        // Für full_name müssen wir speziell sortieren
        if ($sortBy === 'full_name') {
            $query->orderBy('first_name', $sortOrder === 'asc' ? 'asc' : 'desc')
                  ->orderBy('last_name', $sortOrder === 'asc' ? 'asc' : 'desc');
        }

        // Pagination
        $paginated = $query->paginate($perPage);

        // Transform für Frontend
        $userData = $paginated->getCollection()->map(function ($profile) {
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
            'data' => $userData,
            'current_page' => $paginated->currentPage(),
            'per_page' => $paginated->perPage(),
            'total' => $paginated->total(),
            'last_page' => $paginated->lastPage(),
            'from' => $paginated->firstItem(),
            'to' => $paginated->lastItem(),
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

    /**
     * Wendet einen Spalten-Filter auf die Query an
     */
    private function applyColumnFilter($query, array $filter)
    {
        $column = $filter['columnKey'] ?? null;
        $operator = $filter['operator'] ?? null;
        $value = $filter['value'] ?? null;

        if (!$column || !$operator) {
            return $query;
        }

        // Spalten-Mapping (Frontend-Key -> DB-Spalte)
        $columnMapping = [
            'full_name' => ['first_name', 'last_name'],
            'email' => 'email',
            'system_role' => 'system_role',
            'status' => 'status',
            'is_active' => 'is_active',
            'last_login_at' => 'last_login_at',
            'created_at' => 'created_at',
        ];

        // Prüfen ob Spalte erlaubt
        if (!array_key_exists($column, $columnMapping)) {
            return $query;
        }

        $dbColumn = $columnMapping[$column];

        // Spezialfall: full_name (kombiniertes Feld)
        if ($column === 'full_name' && is_array($dbColumn)) {
            return $this->applyTextFilterToMultipleColumns($query, $dbColumn, $operator, $value);
        }

        return match($operator) {
            // Text-Operatoren
            'contains' => $query->where($dbColumn, 'ilike', "%{$value}%"),
            'equals' => $query->where($dbColumn, '=', $value),
            'startsWith' => $query->where($dbColumn, 'ilike', "{$value}%"),
            'endsWith' => $query->where($dbColumn, 'ilike', "%{$value}"),
            'isEmpty' => $query->whereNull($dbColumn)->orWhere($dbColumn, '=', ''),
            'isNotEmpty' => $query->whereNotNull($dbColumn)->where($dbColumn, '!=', ''),

            // Zahlen-Operatoren
            'gt' => $query->where($dbColumn, '>', $value),
            'gte' => $query->where($dbColumn, '>=', $value),
            'lt' => $query->where($dbColumn, '<', $value),
            'lte' => $query->where($dbColumn, '<=', $value),
            'between' => is_array($value) && count($value) === 2
                ? $query->whereBetween($dbColumn, $value)
                : $query,

            // Datum-Operatoren
            'before' => $query->where($dbColumn, '<', $value),
            'after' => $query->where($dbColumn, '>', $value),

            // Select-Operatoren
            'in' => is_array($value)
                ? $query->whereIn($dbColumn, $value)
                : $query,
            'notIn' => is_array($value)
                ? $query->whereNotIn($dbColumn, $value)
                : $query,

            default => $query
        };
    }

    /**
     * Wendet einen Text-Filter auf mehrere Spalten an (OR-Verknüpfung)
     */
    private function applyTextFilterToMultipleColumns($query, array $columns, string $operator, $value)
    {
        return $query->where(function ($q) use ($columns, $operator, $value) {
            foreach ($columns as $column) {
                match($operator) {
                    'contains' => $q->orWhere($column, 'ilike', "%{$value}%"),
                    'equals' => $q->orWhere($column, '=', $value),
                    'startsWith' => $q->orWhere($column, 'ilike', "{$value}%"),
                    'endsWith' => $q->orWhere($column, 'ilike', "%{$value}"),
                    'isEmpty' => $q->orWhereNull($column)->orWhere($column, '=', ''),
                    'isNotEmpty' => $q->orWhereNotNull($column)->where($column, '!=', ''),
                    default => null
                };
            }
        });
    }
}