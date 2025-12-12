<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_category_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('document_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('category_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('tenant_id')->constrained()->onDelete('cascade');
            
            // Assignment metadata
            $table->enum('assignment_type', ['manual', 'ai_auto', 'ai_assisted', 'rule_based', 'bulk'])->default('ai_auto');
            $table->float('confidence_score', 4, 3)->nullable(); // AI confidence 0.000-1.000
            $table->foreignUuid('assigned_by')->nullable()->constrained('accounts');
            $table->text('assignment_reason')->nullable(); // Why this category was chosen
            
            // Assignment context and metadata
            $table->json('assignment_context')->nullable(); // Additional context data
            $table->timestamp('assigned_at')->default(now());
            
            $table->timestamps();
            
            // Ensure unique assignment per document-category pair
            $table->unique(['document_id', 'category_id'], 'unique_document_category');
            
            // Indexes for performance
            $table->index(['document_id']);
            $table->index(['category_id']);
            $table->index(['tenant_id']);
            $table->index(['assignment_type']);
            $table->index(['confidence_score']);
            $table->index(['assigned_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_category_assignments');
    }
};