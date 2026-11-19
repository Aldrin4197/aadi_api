<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('deletion_scheduled_for')->nullable()->index();
        });

        // Deliberately has no foreign key: the record outlives the account for analytics and holds no profile data.
        Schema::create('account_deletions', function (Blueprint $table) {
            $table->id();
            $table->char('user_id', 26)->nullable()->index();
            $table->string('reason', 40);
            $table->string('note', 500)->nullable();
            $table->string('source', 10);
            $table->string('status', 12)->index();
            $table->unsignedInteger('account_age_days')->default(0);
            $table->timestamp('requested_at');
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('retain_until')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_deletions');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('deletion_scheduled_for'));
    }
};
