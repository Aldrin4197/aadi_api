<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->unsignedSmallInteger('minimum_stay_months')->nullable();
            $table->text('contract_rules')->nullable();
        });
        Schema::table('vehicle_listings', function (Blueprint $table) {
            $table->boolean('driver_available')->default(false);
        });
        Schema::create('marketplace_requests', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('service', 20);
            $table->ulid('listing_id');
            $table->string('listing_title');
            $table->foreignUlid('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('provider_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('requested_at');
            $table->boolean('driver_requested')->default(false);
            $table->text('note')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamps();
            $table->index(['customer_id', 'created_at']);
            $table->index(['provider_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_requests');
        Schema::table('vehicle_listings', fn (Blueprint $table) => $table->dropColumn('driver_available'));
        Schema::table('listings', fn (Blueprint $table) => $table->dropColumn(['minimum_stay_months', 'contract_rules']));
    }
};
