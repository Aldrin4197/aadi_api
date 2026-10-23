<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('marketplace_inquiries', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('service', 20);
            // Listings live in three tables; retain the conversation if a listing is removed.
            $table->ulid('listing_id');
            $table->string('listing_title');
            $table->foreignUlid('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('owner_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['service', 'listing_id', 'customer_id'], 'inquiries_listing_customer_unique');
            $table->index(['owner_id', 'updated_at']);
            $table->index(['customer_id', 'updated_at']);
        });
        Schema::create('inquiry_messages', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('inquiry_id')->constrained('marketplace_inquiries')->cascadeOnDelete();
            $table->foreignUlid('sender_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
            $table->index(['inquiry_id', 'id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('inquiry_messages');
        Schema::dropIfExists('marketplace_inquiries');
    }
};
