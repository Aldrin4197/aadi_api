<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $t) { $t->boolean('is_admin')->default(false); });
        Schema::table('service_listings', function (Blueprint $t) {
            $t->decimal('latitude', 10, 7)->nullable();
            $t->decimal('longitude', 10, 7)->nullable();
            $t->index(['latitude', 'longitude']);
        });
    }
    public function down(): void {
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('is_admin'));
        Schema::table('service_listings', function (Blueprint $t) {
            $t->dropIndex(['latitude', 'longitude']);
            $t->dropColumn(['latitude', 'longitude']);
        });
    }
};
