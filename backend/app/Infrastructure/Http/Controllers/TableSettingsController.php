<?php

namespace App\Infrastructure\Http\Controllers;

use App\Core\Shared\Models\TableSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * TableSettingsController
 *
 * Verwaltet Benutzer-spezifische Tabelleneinstellungen
 * (Spaltenreihenfolge, Spaltenbreiten)
 */
class TableSettingsController extends Controller
{
    /**
     * Holt die Settings für eine Tabelle
     *
     * GET /api/table-settings?table_key=tenant.users
     */
    public function show(Request $request): JsonResponse
    {
        $request->validate([
            'table_key' => 'required|string|max:100',
        ]);

        $settings = TableSettings::forCurrentUser()
            ->forTable($request->table_key)
            ->first();

        if (!$settings) {
            return response()->json([
                'data' => [
                    'column_order' => null,
                    'column_widths' => null,
                ]
            ]);
        }

        return response()->json([
            'data' => [
                'column_order' => $settings->column_order,
                'column_widths' => $settings->column_widths,
            ]
        ]);
    }

    /**
     * Speichert oder aktualisiert die Settings
     *
     * PUT /api/table-settings
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'table_key' => 'required|string|max:100',
            'column_order' => 'nullable|array',
            'column_order.*' => 'string',
            'column_widths' => 'nullable|array',
        ]);

        $settings = TableSettings::getOrCreate($validated['table_key']);

        // Nur die übergebenen Felder aktualisieren
        if (array_key_exists('column_order', $validated)) {
            $settings->column_order = $validated['column_order'];
        }

        if (array_key_exists('column_widths', $validated)) {
            $settings->column_widths = $validated['column_widths'];
        }

        $settings->save();

        return response()->json([
            'message' => 'Einstellungen erfolgreich gespeichert',
            'data' => [
                'column_order' => $settings->column_order,
                'column_widths' => $settings->column_widths,
            ]
        ]);
    }

    /**
     * Setzt die Settings auf Standard zurück
     *
     * DELETE /api/table-settings?table_key=tenant.users
     */
    public function destroy(Request $request): JsonResponse
    {
        $request->validate([
            'table_key' => 'required|string|max:100',
        ]);

        TableSettings::forCurrentUser()
            ->forTable($request->table_key)
            ->delete();

        return response()->json([
            'message' => 'Einstellungen erfolgreich zurückgesetzt'
        ]);
    }
}
