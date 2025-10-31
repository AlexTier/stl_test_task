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
        Schema::create('holds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('slot_id')
                  ->constrained('slots')
                  ->onDelete('cascade');
            $table->uuid('idempotency_key');
            $table->enum('status', ['held','confirmed','cancelled'])
                  ->default('held');
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->unique('idempotency_key');
            $table->index('slot_id');
            $table->index('status');
            $table->index(['slot_id','status']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('holds');
    }
};