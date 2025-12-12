<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('uploaded_by')->constrained('accounts')->onDelete('cascade');
            
            // Basic document metadata
            $table->string('original_filename', 255);
            $table->string('stored_filename', 255);
            $table->string('mime_type', 100);
            $table->bigInteger('file_size');
            $table->string('file_hash', 64)->unique(); // SHA-256 for deduplication
            $table->string('storage_path', 500);
            
            // Document properties
            $table->string('title', 255)->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable(); // Custom metadata fields
            
            // AI-enhanced fields
            $table->json('ai_classification')->nullable(); // AI-generated categories
            $table->json('ai_metadata')->nullable(); // Extracted metadata
            $table->json('ai_tags')->nullable(); // AI-generated tags array
            $table->text('ai_summary')->nullable(); // AI-generated summary
            $table->float('ai_confidence_score', 4, 3)->nullable(); // 0.000-1.000
            $table->timestamp('ai_processed_at')->nullable();
            $table->enum('ai_processing_status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            
            // Content extraction
            $table->text('extracted_text')->nullable(); // Full extracted text
            $table->json('structured_data')->nullable(); // Extracted structured data
            $table->string('language', 10)->nullable(); // Detected language
            $table->integer('page_count')->nullable();
            
            // Access control and security
            $table->enum('visibility', ['public', 'internal', 'confidential', 'restricted'])->default('internal');
            $table->json('access_permissions')->nullable(); // User/role permissions
            $table->boolean('requires_approval')->default(false);
            $table->boolean('is_encrypted')->default(false);
            
            // Document lifecycle
            $table->enum('status', ['draft', 'active', 'archived', 'deleted'])->default('active');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->foreignUuid('archived_by')->nullable()->constrained('accounts');
            
            // Versioning
            $table->uuid('parent_document_id')->nullable();
            $table->integer('version')->default(1);
            $table->boolean('is_latest_version')->default(true);
            
            // Workflow integration
            $table->json('workflow_data')->nullable(); // Current workflow state
            $table->string('workflow_status', 50)->nullable();
            $table->timestamp('workflow_started_at')->nullable();
            $table->timestamp('workflow_completed_at')->nullable();
            
            // Analytics and usage tracking
            $table->integer('download_count')->default(0);
            $table->integer('view_count')->default(0);
            $table->timestamp('last_accessed_at')->nullable();
            $table->foreignUuid('last_accessed_by')->nullable()->constrained('accounts');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'uploaded_by']);
            $table->index(['tenant_id', 'created_at']);
            $table->index(['file_hash']);
            $table->index(['ai_processing_status']);
            $table->index(['workflow_status']);
            $table->index(['mime_type']);
            $table->index(['parent_document_id', 'version']);
            
            // Full-text search index on extracted_text
            $table->index(['extracted_text']);
        });

        // Add foreign key constraint after table creation
        Schema::table('documents', function (Blueprint $table) {
            $table->foreign('parent_document_id')->references('id')->on('documents')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};