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
        Schema::create('viewing_requests', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('listing_id')->constrained()->cascadeOnDelete();
            $table->string('listing_title'); // Snapshot
            
            $table->foreignUlid('tenant_id')->constrained('users')->cascadeOnDelete();
            $table->string('tenant_name'); // Snapshot
            
            $table->foreignUlid('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('owner_name'); // Snapshot
            
            $table->timestamp('requested_at');
            $table->timestamp('proposed_at')->nullable();
            
            $table->string('status')->default('pending'); // ViewingStatus enum
            $table->text('note')->nullable();
            
            $table->timestamps();
            
            $table->index(['tenant_id', 'status']);
            $table->index(['owner_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('viewing_requests');
    }
};
