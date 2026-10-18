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
        Schema::create('service_listings', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('provider_id')->constrained('users', 'id')->onDelete('cascade');
            
            // Basic Info
            $table->string('title');
            $table->text('description');
            $table->string('service_type'); // Plumbing, Electrical, Cleaning, Tutoring, etc.
            $table->string('service_category')->nullable(); // Home Services, Professional Services, etc.
            $table->json('skills_required')->nullable();
            
            // Pricing
            $table->enum('price_type', ['fixed', 'hourly', 'per_project'])->default('fixed');
            $table->decimal('price_amount', 10, 2);
            $table->string('currency', 3)->default('PHP');
            
            // Service Details
            $table->string('service_area'); // Location/area covered
            $table->json('availability_schedule')->nullable(); // JSON of available days/hours
            $table->integer('response_time_minutes')->nullable(); // Average response time
            $table->integer('estimated_duration_minutes')->nullable();
            
            // Status & Media
            $table->enum('status', ['available', 'busy', 'inactive'])->default('available');
            $table->json('images')->nullable();
            $table->json('certifications')->nullable(); // URLs or file paths
            
            // Metrics
            $table->unsignedInteger('views_count')->default(0);
            $table->decimal('rating_average', 3, 2)->default(0);
            $table->unsignedInteger('rating_count')->default(0);
            $table->unsignedInteger('completion_count')->default(0);
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('provider_id');
            $table->index('service_type');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_listings');
    }
};
