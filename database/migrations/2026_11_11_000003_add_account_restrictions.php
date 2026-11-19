<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('restriction_type', 20)->nullable();
            $table->timestamp('restricted_until')->nullable();
            $table->string('restriction_reason', 40)->nullable();
        });
        Schema::create('account_restriction_events', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 20);
            $table->string('reason', 40)->nullable();
            $table->timestamp('until')->nullable();
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_restriction_events');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['restriction_type', 'restricted_until', 'restriction_reason']));
    }
};
