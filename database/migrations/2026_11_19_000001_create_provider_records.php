<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('provider_records', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('provider_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('request_id')->nullable()->unique()->constrained('marketplace_requests')->nullOnDelete();
            $table->foreignUlid('source_viewing_id')->nullable()->unique()->constrained('provider_records')->nullOnDelete();
            $table->string('service', 20);
            $table->ulid('listing_id');
            $table->string('listing_title');
            $table->string('kind', 20);
            $table->string('status', 20)->default('scheduled');
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->unsignedBigInteger('agreed_cents')->nullable();
            $table->timestamps();
            $table->index(['provider_id', 'status', 'starts_on']);
            $table->index(['service', 'listing_id']);
        });
        Schema::create('provider_payments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('provider_record_id')->constrained('provider_records')->cascadeOnDelete();
            $table->unsignedBigInteger('amount_cents');
            $table->date('paid_on');
            $table->string('period', 60)->nullable();
            $table->string('note', 500)->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->timestamps();
        });
        DB::table('marketplace_requests')->where('status', 'accepted')->orderBy('id')->chunk(200, function ($requests) {
            foreach ($requests as $request) {
                DB::table('provider_records')->insert([
                    'id' => (string) Str::ulid(), 'provider_id' => $request->provider_id, 'customer_id' => $request->customer_id,
                    'request_id' => $request->id, 'service' => $request->service, 'listing_id' => $request->listing_id,
                    'listing_title' => $request->listing_title, 'kind' => $request->service === 'spaces' ? 'viewing' : ($request->service === 'haul' ? 'rental' : 'job'),
                    'status' => 'scheduled', 'starts_on' => substr($request->requested_at, 0, 10),
                    'ends_on' => $request->requested_until, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_payments');
        Schema::dropIfExists('provider_records');
    }
};
