<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aadi_plus_perks', function (Blueprint $table) {
            $table->string('key', 40)->primary();
            $table->boolean('enabled')->default(true);
            $table->unsignedInteger('free_limit')->nullable();
            $table->unsignedInteger('plus_limit')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aadi_plus_perks');
    }
};
