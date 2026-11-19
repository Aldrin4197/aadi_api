<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('account_reports', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('reporter_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('reported_id')->constrained('users')->cascadeOnDelete();
            $table->text('reason');
            $table->string('status', 20)->default('pending');
            $table->unsignedTinyInteger('applied_points')->default(0);
            $table->text('review_note')->nullable();
            $table->foreignUlid('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });
        Schema::create('marketplace_listing_views', function (Blueprint $table) {
            $table->id();
            $table->string('service', 20);
            $table->ulid('listing_id');
            $table->foreignUlid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address')->nullable();
            $table->timestamp('viewed_at');
            $table->index(['service', 'listing_id', 'viewed_at']);
        });
        Schema::create('account_report_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('account_report_id')->constrained('account_reports')->cascadeOnDelete();
            $table->foreignUlid('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 20);
            $table->unsignedTinyInteger('points')->default(0);
            $table->text('note');
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_report_reviews');
        Schema::dropIfExists('marketplace_listing_views');
        Schema::dropIfExists('account_reports');
    }
};
