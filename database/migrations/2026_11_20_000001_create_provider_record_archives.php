<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('provider_record_archives', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('provider_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('service', 20);
            $table->json('data');
            $table->timestamp('retain_until')->index();
            $table->timestamps();
            $table->index(['provider_id', 'retain_until']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_record_archives');
    }
};
