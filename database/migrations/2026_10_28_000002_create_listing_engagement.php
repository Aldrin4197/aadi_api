<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        foreach (['marketplace_saves', 'marketplace_reviews'] as $name) {
            Schema::create($name, function (Blueprint $table) use ($name) {
                $table->id();
                $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
                $table->string('service', 16);
                $table->char('listing_id', 26);
                if ($name === 'marketplace_reviews') {
                    $table->unsignedTinyInteger('rating');
                    $table->text('body');
                }
                $table->timestamps();
                $table->unique(['user_id', 'service', 'listing_id']);
                $table->index(['service', 'listing_id']);
            });
        }
    }
    public function down(): void {
        Schema::dropIfExists('marketplace_reviews');
        Schema::dropIfExists('marketplace_saves');
    }
};
