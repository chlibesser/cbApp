<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_shares', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('document_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('shared_by')->constrained('accounts')->onDelete('cascade');
            $table->foreignUuid('tenant_id')->constrained()->onDelete('cascade');
            
            // Share target - either user or external
            $table->foreignUuid('shared_with_user')->nullable()->constrained('accounts')->onDelete('cascade');
            $table->string('shared_with_email')->nullable(); // External sharing
            
            // Access control
            $table->enum('permission_level', ['view', 'download', 'comment', 'edit'])->default('view');
            $table->json('specific_permissions')->nullable(); // Granular permissions
            
            // Share configuration
            $table->string('share_token', 64)->unique()->nullable(); // For external/anonymous access
            $table->boolean('requires_password')->default(false);
            $table->string('password_hash')->nullable();
            $table->boolean('allow_public_access')->default(false);
            
            // Expiration and limits
            $table->timestamp('expires_at')->nullable();
            $table->integer('download_limit')->nullable(); // Max downloads allowed
            $table->integer('view_limit')->nullable(); // Max views allowed
            $table->integer('downloads_used')->default(0);
            $table->integer('views_used')->default(0);
            
            // Notifications
            $table->boolean('notify_on_access')->default(false);
            $table->boolean('notify_on_download')->default(false);
            $table->string('notification_email')->nullable(); // Where to send notifications
            
            // Status and tracking
            $table->boolean('is_active')->default(true);
            $table->timestamp('first_accessed_at')->nullable();
            $table->timestamp('last_accessed_at')->nullable();
            $table->json('access_log')->nullable(); // Track access history
            
            // Share context
            $table->text('share_message')->nullable(); // Message to recipient
            $table->json('share_context')->nullable(); // Additional context data
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['document_id']);
            $table->index(['shared_with_user']);
            $table->index(['shared_with_email']);
            $table->index(['share_token']);
            $table->index(['tenant_id', 'is_active']);
            $table->index(['expires_at']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_shares');
    }
};