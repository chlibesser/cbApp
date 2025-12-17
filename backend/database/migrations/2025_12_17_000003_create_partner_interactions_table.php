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
        Schema::create('partner_interactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('partner_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('accounts')->restrictOnDelete();
            $table->string('type')->default('note'); // meeting, call, email, note, document
            $table->string('subject');
            $table->text('content')->nullable();
            $table->datetime('interaction_date');
            $table->json('participants')->nullable(); // Array of participant names/emails
            $table->json('attachments')->nullable(); // Array of attachment references
            $table->date('follow_up_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['partner_id', 'interaction_date']);
            $table->index(['user_id', 'interaction_date']);
            $table->index('follow_up_date');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_interactions');
    }
};