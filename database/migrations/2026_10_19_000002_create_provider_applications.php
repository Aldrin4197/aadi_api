<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('provider_applications', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->string('service');
            $table->string('status')->default('pending');
            $table->string('display_name');
            $table->string('phone', 40);
            $table->string('area');
            $table->text('description');
            $table->json('documents');
            $table->text('review_note')->nullable();
            $table->foreignUlid('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at');
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'service']);
        });
    }
    public function down(): void { Schema::dropIfExists('provider_applications'); }
};
