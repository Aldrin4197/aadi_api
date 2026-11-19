<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        foreach ([
            'listings' => 'active',
            'vehicle_listings' => 'available',
            'service_listings' => 'available',
        ] as $table => $publishedStatus) {
            Schema::table($table, function (Blueprint $table) {
                $table->timestamp('published_at')->nullable()->index();
            });

            DB::table($table)
                ->where('status', $publishedStatus)
                ->update(['published_at' => DB::raw('created_at')]);
        }
    }

    public function down(): void
    {
        foreach (['listings', 'vehicle_listings', 'service_listings'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn('published_at');
            });
        }
    }
};
