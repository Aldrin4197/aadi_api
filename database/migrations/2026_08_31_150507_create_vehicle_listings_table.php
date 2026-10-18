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
        Schema::create('vehicle_listings', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('owner_id')->constrained('users')->onDelete('cascade');
            
            // Basic Info
            $table->string('title');
            $table->text('description');
            $table->string('vehicle_type'); // Van, Truck, Motorcycle, etc.
            $table->string('brand');
            $table->string('model');
            $table->integer('year');
            $table->string('plate_number')->nullable();
            
            // Vehicle Details
            $table->integer('seating_capacity')->nullable();
            $table->string('fuel_type')->nullable(); // Gasoline, Diesel, Electric, Hybrid
            $table->string('transmission')->nullable(); // Manual, Automatic
            $table->json('features')->nullable(); // AC, GPS, etc.
            
            // Pricing
            $table->decimal('price_per_day', 10, 2);
            $table->decimal('price_per_week', 10, 2)->nullable();
            $table->decimal('price_per_month', 10, 2)->nullable();
            $table->string('currency', 3)->default('PHP');
            
            // Location
            $table->string('location');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            
            // Status & Media
            $table->enum('status', ['available', 'rented', 'maintenance', 'inactive'])->default('available');
            $table->json('images')->nullable();
            
            // Metrics
            $table->unsignedInteger('views_count')->default(0);
            $table->decimal('rating_average', 3, 2)->default(0);
            $table->unsignedInteger('rating_count')->default(0);
            
            // Availability
            $table->date('available_from')->nullable();
            $table->date('available_to')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('owner_id');
            $table->index('vehicle_type');
            $table->index('status');
            $table->index(['latitude', 'longitude']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_listings');
    }
};
