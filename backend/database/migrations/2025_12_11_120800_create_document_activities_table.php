<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_activities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('document_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('tenant_id')->constrained()->onDelete('cascade');
            
            // Actor information
            $table->foreignUuid('actor_id')->nullable()->constrained('accounts')->onDelete('set null');
            $table->string('actor_type')->default('user'); // user, system, ai, external
            $table->string('actor_name')->nullable(); // For external actors
            
            // Activity details
            $table->string('activity_type', 50); // uploaded, viewed, downloaded, shared, categorized, etc.
            $table->string('activity_description', 500);
            $table->json('activity_data')->nullable(); // Structured activity data
            
            // Context information
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('session_id')->nullable();
            $table->json('request_metadata')->nullable(); // Additional request context
            
            // Activity classification
            $table->enum('activity_category', ['access', 'modification', 'sharing', 'workflow', 'ai_processing', 'admin', 'security'])->default('access');
            $table->enum('activity_level', ['info', 'warning', 'error', 'critical'])->default('info');
            
            // Related entities
            $table->string('related_entity_type')->nullable(); // workflow, category, share, etc.
            $table->string('related_entity_id')->nullable();
            $table->json('related_entities')->nullable(); // Multiple related entities
            
            // Performance and diagnostics
            $table->integer('processing_time_ms')->nullable();
            $table->boolean('was_successful')->default(true);
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            
            $table->timestamp('occurred_at')->default(now());
            $table->timestamps();
            
            // Indexes for performance and querying
            $table->index(['document_id', 'occurred_at']);
            $table->index(['actor_id', 'occurred_at']);
            $table->index(['tenant_id', 'occurred_at']);
            $table->index(['activity_type', 'occurred_at']);
            $table->index(['activity_category']);
            $table->index(['activity_level']);
            $table->index(['was_successful']);
            $table->index(['ip_address']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_activities');
    }
};