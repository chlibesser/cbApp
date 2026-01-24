<?php

namespace App\Infrastructure\Http\Controllers;

use App\Core\Shared\Models\TableFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TableFilterController extends Controller
{
    /**
     * GET /api/table-filters?table_key=tenant.users
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'table_key' => 'required|string|max:100',
        ], [
            'table_key.required' => 'Der Tabellen-Schlüssel ist erforderlich.',
        ]);

        $filters = TableFilter::forCurrentUser()
            ->forTable($request->table_key)
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $filters,
        ]);
    }

    /**
     * POST /api/table-filters
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'table_key' => 'required|string|max:100',
            'name' => 'required|string|max:100',
            'color' => 'nullable|string|max:20',
            'filter_state' => 'required|array',
            'show_as_button' => 'boolean',
        ], [
            'table_key.required' => 'Der Tabellen-Schlüssel ist erforderlich.',
            'name.required' => 'Der Filter-Name ist erforderlich.',
            'name.max' => 'Der Filter-Name darf maximal 100 Zeichen lang sein.',
            'filter_state.required' => 'Der Filter-Status ist erforderlich.',
        ]);

        $validated['account_id'] = auth()->id();

        $filter = TableFilter::create($validated);

        return response()->json([
            'message' => 'Filter erfolgreich erstellt',
            'data' => $filter,
        ], 201);
    }

    /**
     * PATCH /api/table-filters/{tableFilter}
     */
    public function update(Request $request, TableFilter $tableFilter): JsonResponse
    {
        if ($tableFilter->account_id !== auth()->id()) {
            return response()->json([
                'message' => 'Nicht autorisiert.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'color' => 'sometimes|string|max:20',
            'filter_state' => 'sometimes|array',
            'show_as_button' => 'sometimes|boolean',
        ], [
            'name.max' => 'Der Filter-Name darf maximal 100 Zeichen lang sein.',
        ]);

        $tableFilter->update($validated);

        return response()->json([
            'message' => 'Filter erfolgreich aktualisiert',
            'data' => $tableFilter->fresh(),
        ]);
    }

    /**
     * DELETE /api/table-filters/{tableFilter}
     */
    public function destroy(TableFilter $tableFilter): JsonResponse
    {
        if ($tableFilter->account_id !== auth()->id()) {
            return response()->json([
                'message' => 'Nicht autorisiert.',
            ], 403);
        }

        $tableFilter->delete();

        return response()->json([
            'message' => 'Filter erfolgreich gelöscht',
        ]);
    }
}
