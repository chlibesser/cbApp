<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('category_groups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants');
            
            // Gruppe-Metadaten
            $table->string('name', 100);                    // 'Dokumenttyp'
            $table->string('slug', 100);                    // 'document_type'
            $table->text('description')->nullable();        // Gruppe-Beschreibung
            
            // Selection-Verhalten
            $table->string('selection_type', 20)->default('single');  // SelectionType enum
            $table->boolean('is_required')->default(false);           // Pflichtfeld
            
            // AI-Integration
            $table->boolean('ai_enabled')->default(true);               // AI-Kategorisierung aktiviert
            $table->text('ai_prompt_context')->nullable();              // Zusätzlicher AI-Kontext
            $table->float('ai_confidence_threshold')->default(0.7);     // Mindest-Vertrauen für Auto-Assignment
            
            // Display-Settings
            $table->integer('display_order')->default(0);               // Reihenfolge in UI
            $table->string('icon', 50)->nullable();                     // Icon für UI
            $table->string('color', 20)->nullable();                    // Farbe für UI
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);               // System-Gruppen nicht löschbar
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->unique(['tenant_id', 'slug']);
            $table->index(['tenant_id', 'is_active']);
            $table->index(['tenant_id', 'display_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_groups');
    }
};
