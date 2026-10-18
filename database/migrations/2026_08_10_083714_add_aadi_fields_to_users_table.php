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
            $table->string('landlord_status')->default('none')->after('password');
            $table->string('phone')->nullable()->after('landlord_status');
            $table->string('avatar_url')->nullable()->after('phone');
            $table->decimal('rating_average', 3, 2)->default(0)->after('avatar_url');
            $table->unsignedInteger('rating_count')->default(0)->after('rating_average');
            $table->unsignedTinyInteger('trust_score')->default(0)->after('rating_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'landlord_status',
                'phone',
                'avatar_url',
                'rating_average',
                'rating_count',
                'trust_score',
            ]);
        });
    }
};
