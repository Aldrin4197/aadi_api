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
        Schema::create('listings', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('owner_name'); // Snapshot
            $table->string('title');
            $table->text('description');
            $table->string('type'); // ListingType enum
            $table->decimal('price', 10, 2);
            $table->string('currency')->default('₱');
            
            // Location (GeoPoint embedded)
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            
            $table->json('photos')->nullable(); // Array of URLs
            $table->json('amenities')->nullable(); // Array of strings
            
            $table->string('status')->default('active'); // ListingStatus enum
            $table->string('property_verification')->default('none'); // VerificationStatus enum
            
            $table->decimal('rating_average', 3, 2)->default(0);
            $table->unsignedInteger('rating_count')->default(0);
            
            $table->timestamps();
            
            $table->index(['status', 'type']);
            $table->index(['lat', 'lng']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
};
