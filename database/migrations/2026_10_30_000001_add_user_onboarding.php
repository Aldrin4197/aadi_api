<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->json('onboarding_data')->nullable();
            $table->string('onboarding_status', 20)->default('pending');
        });
        // Existing accounts may opt in from their profile; only new accounts auto-open.
        DB::table('users')->update(['onboarding_status'=>'skipped']);
    }
    public function down(): void {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['onboarding_data','onboarding_status']));
    }
};
