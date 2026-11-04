<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('reference', 16)->unique();
            $table->char('user_id', 26)->nullable();
            $table->string('name', 120);
            $table->string('email', 190);
            $table->string('category', 24);
            $table->string('subject', 160);
            $table->text('message');
            $table->string('status', 16)->default('open');
            $table->string('priority', 16)->default('normal');
            $table->string('source', 24)->default('home');
            $table->char('assigned_to', 26)->nullable();
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('assigned_to')->references('id')->on('users')->nullOnDelete();
            $table->index(['status', 'priority']);
            $table->index('category');
            $table->index('created_at');
            $table->index('email');
        });

        Schema::create('support_ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->char('ticket_id', 26);
            $table->char('user_id', 26)->nullable();
            $table->string('kind', 12)->default('reply'); // reply (sent to requester), note (internal), event (audit trail)
            $table->text('body');
            $table->boolean('emailed')->default(false);
            $table->timestamps();
            $table->foreign('ticket_id')->references('id')->on('support_tickets')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['ticket_id', 'created_at']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('support_ticket_messages');
        Schema::dropIfExists('support_tickets');
    }
};
