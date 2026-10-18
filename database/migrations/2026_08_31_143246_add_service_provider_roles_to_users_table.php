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
        Schema::table('users', function (Blueprint $table) {
            // UV Rental Owner fields
            $table->enum('vehicle_owner_status', ['none', 'pending', 'approved', 'rejected'])
                ->default('none')
                ->after('landlord_status');
            $table->string('vehicle_owner_business_name')->nullable()->after('vehicle_owner_status');
            $table->json('vehicle_owner_licenses')->nullable()->after('vehicle_owner_business_name');
            $table->json('vehicle_owner_inventory')->nullable()->after('vehicle_owner_licenses');
            $table->json('vehicle_owner_certificates')->nullable()->after('vehicle_owner_inventory');
            
            // Service Provider fields
            $table->enum('service_provider_status', ['none', 'pending', 'approved', 'rejected'])
                ->default('none')
                ->after('vehicle_owner_certificates');
            $table->string('service_provider_business_name')->nullable()->after('service_provider_status');
            $table->json('service_provider_skills')->nullable()->after('service_provider_business_name');
            $table->json('service_provider_certifications')->nullable()->after('service_provider_skills');
            $table->string('service_provider_service_area')->nullable()->after('service_provider_certifications');
            $table->integer('service_provider_years_experience')->nullable()->after('service_provider_service_area');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'vehicle_owner_status',
                'vehicle_owner_business_name',
                'vehicle_owner_licenses',
                'vehicle_owner_inventory',
                'vehicle_owner_certificates',
                'service_provider_status',
                'service_provider_business_name',
                'service_provider_skills',
                'service_provider_certifications',
                'service_provider_service_area',
                'service_provider_years_experience',
            ]);
        });
    }
};
