<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('table_filters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('account_id')->constrained()->cascadeOnDelete();
            $table->string('table_key', 100);
            $table->string('name', 100);
            $table->string('color', 20)->default('#1976D2');
            $table->json('filter_state');
            $table->boolean('show_as_button')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['account_id', 'table_key']);
            $table->index('table_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('table_filters');
    }
};
