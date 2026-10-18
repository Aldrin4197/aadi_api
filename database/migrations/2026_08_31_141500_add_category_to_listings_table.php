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
        Schema::table('listings', function (Blueprint $table) {
            // Add category field to distinguish between main offerings
            $table->string('category')->default('space_rental')->after('description');
            
            // Make type nullable since it will be category-specific
            $table->string('type')->nullable()->change();
            
            // Add flexible attributes as JSON for category-specific data
            // For vehicles: capacity, fuel_type, transmission, year, etc.
            // For skills: experience_years, certifications, service_area, etc.
            $table->json('attributes')->nullable()->after('amenities');
            
            // Add pricing type field
            $table->string('pricing_type')->default('fixed')->after('price');
            
            // Add index for category filtering
            $table->index(['category', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropIndex(['category', 'status']);
            $table->dropColumn(['category', 'attributes', 'pricing_type']);
        });
    }
};
