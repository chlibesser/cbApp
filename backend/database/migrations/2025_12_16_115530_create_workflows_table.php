<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflows', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignUuid('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('created_by')->constrained('accounts')->onDelete('cascade');
            $table->json('nodes')->nullable(); // Vue Flow nodes
            $table->json('edges')->nullable(); // Vue Flow edges
            $table->json('metadata')->nullable(); // Additional workflow metadata
            $table->enum('status', ['draft', 'active', 'inactive'])->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflows');
    }
};