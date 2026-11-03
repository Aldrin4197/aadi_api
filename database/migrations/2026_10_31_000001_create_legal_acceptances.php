<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('legal_acceptances', function (Blueprint $table) {
            $table->id();
            $table->char('user_id',26);
            $table->string('context',20);
            $table->string('version',80);
            $table->char('content_hash',64);
            $table->json('snapshot');
            $table->timestamp('accepted_at');
            $table->unique(['user_id','context','version'], 'legal_acceptance_unique');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
    public function down(): void { Schema::dropIfExists('legal_acceptances'); }
};
