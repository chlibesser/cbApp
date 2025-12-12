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
        Schema::create('categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('category_group_id')->constrained('category_groups')->onDelete('cascade');
            $table->foreignUuid('tenant_id')->constrained('tenants');  // Denormalisiert für Performance
            
            // Kategorie-Metadaten
            $table->string('name', 100);                    // 'Rechnung'
            $table->string('slug', 100);                    // 'invoice'
            $table->text('description')->nullable();        // Kurze Beschreibung
            
            // Detaillierte AI-Beschreibungen
            $table->text('ai_positive_description');        // Was GEHÖRT zu dieser Kategorie
            $table->text('ai_negative_description')->nullable();  // Was GEHÖRT NICHT zu dieser Kategorie
            $table->json('ai_keywords')->nullable();        // Schlüsselwörter für AI
            $table->json('ai_examples')->nullable();        // Beispiele für AI-Training
            
            // Kategorie-Eigenschaften
            $table->boolean('is_default')->default(false);              // Default-Auswahl
            $table->boolean('requires_approval')->default(false);       // Manuelle Bestätigung erforderlich
            
            // Display-Settings
            $table->integer('display_order')->default(0);
            $table->string('icon', 50)->nullable();
            $table->string('color', 20)->nullable();
            
            // Status
            $table->boolean('is_active')->default(true);
            
            // Statistiken
            $table->integer('usage_count')->default(0);                 // Wie oft verwendet
            $table->timestamp('last_used_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->unique(['category_group_id', 'slug']);
            $table->index(['tenant_id', 'is_active']);
            $table->index(['category_group_id', 'display_order']);
            $table->index(['tenant_id', 'is_default']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
