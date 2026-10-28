<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->text('bio')->nullable();
            $table->string('city')->nullable();
            $table->string('address')->nullable();
            $table->string('avatar_path')->nullable();
        });
        Schema::table('provider_applications', function (Blueprint $table) { $table->json('details')->nullable(); });
    }
    public function down(): void {
        Schema::table('provider_applications', fn (Blueprint $table) => $table->dropColumn('details'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['bio','city','address','avatar_path']));
    }
};
