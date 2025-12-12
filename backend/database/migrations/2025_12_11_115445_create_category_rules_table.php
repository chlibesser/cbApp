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
        Schema::create('category_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('category_id')->constrained('categories')->onDelete('cascade');
            $table->foreignUuid('tenant_id')->constrained('tenants');
            
            // Regel-Definition
            $table->string('rule_type', 20);               // CategoryRuleType enum
            $table->text('rule_value');                    // Regel-Spezifikation
            $table->string('rule_operator', 20)->default('contains'); // CategoryRuleOperator enum
            
            // Regel-Eigenschaften
            $table->float('weight')->default(1.0);                      // Gewichtung für AI-Entscheidung
            $table->boolean('is_mandatory')->default(false);            // Muss-Kriterium
            $table->boolean('is_exclusion')->default(false);            // Ausschluss-Kriterium
            
            // Status
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['category_id', 'is_active']);
            $table->index(['tenant_id', 'rule_type']);
            $table->index(['category_id', 'weight']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_rules');
    }
};
