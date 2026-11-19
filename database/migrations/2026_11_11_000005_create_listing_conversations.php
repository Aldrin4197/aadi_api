<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('listing_conversations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('service', 20);
            $table->ulid('listing_id');
            $table->string('listing_title');
            $table->foreignUlid('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('provider_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['service', 'listing_id', 'customer_id', 'provider_id'], 'listing_conversation_participants_unique');
            $table->index(['customer_id', 'updated_at']);
            $table->index(['provider_id', 'updated_at']);
        });

        Schema::create('listing_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('listing_conversation_id')->constrained('listing_conversations')->cascadeOnDelete();
            $table->foreignUlid('sender_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
            $table->index(['listing_conversation_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_messages');
        Schema::dropIfExists('listing_conversations');
    }
};
