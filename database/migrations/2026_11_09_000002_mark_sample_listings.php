<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        foreach (['listings', 'vehicle_listings', 'service_listings'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->boolean('is_sample')->default(false));
            DB::table($name)->where(fn ($query) => $query
                ->where('id', 'like', '01JAD1DEM%')
                ->orWhere('id', 'like', '01JAD1ACC%'))->update(['is_sample' => true]);
        }
    }

    public function down(): void
    {
        foreach (['listings', 'vehicle_listings', 'service_listings'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropColumn('is_sample'));
        }
    }
};
