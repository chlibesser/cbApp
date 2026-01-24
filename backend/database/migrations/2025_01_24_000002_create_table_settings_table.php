<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Speichert Benutzer-spezifische Tabelleneinstellungen:
     * - Spaltenreihenfolge
     * - Spaltenbreiten
     */
    public function up(): void
    {
        Schema::create('table_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('account_id')->constrained()->cascadeOnDelete();
            $table->string('table_key', 100); // z.B. "tenant.users", "admin.accounts"
            $table->json('column_order')->nullable(); // ["name", "email", "status"]
            $table->json('column_widths')->nullable(); // {"name": 200, "email": 250}
            $table->timestamps();

            // Unique constraint: Ein Setting pro User pro Tabelle
            $table->unique(['account_id', 'table_key']);

            // Index für schnelle Abfragen
            $table->index(['account_id', 'table_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('table_settings');
    }
};
