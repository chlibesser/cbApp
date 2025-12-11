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
        Schema::create('profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('account_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('tenant_id')->constrained()->onDelete('cascade');
            $table->string('display_name');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('system_role')->default('tenant_member');
            $table->boolean('is_active')->default(true);
            $table->string('avatar')->nullable();
            $table->json('preferences')->nullable();
            $table->timestamps();
            
            $table->unique(['account_id', 'tenant_id']);
            $table->index(['tenant_id', 'account_id']);
            $table->index('system_role');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
