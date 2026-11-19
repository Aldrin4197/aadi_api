<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        foreach (['listings', 'vehicle_listings', 'service_listings'] as $table) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->boolean('availability_paused')->default(false));
        }
        Schema::table('marketplace_requests', function (Blueprint $table) {
            $table->date('requested_until')->nullable();
            $table->index(['service', 'listing_id', 'status', 'requested_at'], 'request_availability_index');
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_requests', function (Blueprint $table) {
            $table->dropIndex('request_availability_index');
            $table->dropColumn('requested_until');
        });
        foreach (['listings', 'vehicle_listings', 'service_listings'] as $table) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropColumn('availability_paused'));
        }
    }
};
