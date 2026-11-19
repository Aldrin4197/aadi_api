<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class QueueHealthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('The SQLite PDO extension is required.');
        }
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        config(['queue.default' => 'database', 'queue.failed.driver' => 'database-uuids']);
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue');
            $table->unsignedInteger('created_at');
            $table->unsignedInteger('available_at');
        });
        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->timestamp('failed_at');
        });
    }

    public function test_future_jobs_do_not_make_a_healthy_queue_unhealthy(): void
    {
        DB::table('jobs')->insert(['queue' => 'default', 'created_at' => now()->subHour()->timestamp, 'available_at' => now()->addHour()->timestamp]);
        DB::table('jobs')->insert(['queue' => 'default', 'created_at' => now()->subHour()->timestamp, 'available_at' => now()->subMinute()->timestamp]);
        $this->artisan('aadi:queue-health')->assertExitCode(0);
    }

    public function test_a_stalled_job_is_reported(): void
    {
        DB::table('jobs')->insert(['queue' => 'default', 'created_at' => now()->subMinutes(6)->timestamp, 'available_at' => now()->subMinutes(6)->timestamp]);
        Log::shouldReceive('warning')->once()->with('Queue health requires attention.', \Mockery::on(
            fn ($context) => $context['oldest_ready_job_seconds'] >= 300 && $context['failed_jobs_last_24_hours'] === 0
        ));
        $this->artisan('aadi:queue-health')->assertExitCode(1);
    }

    public function test_recently_failed_jobs_are_reported(): void
    {
        DB::table('failed_jobs')->insert(['failed_at' => now()]);
        Log::shouldReceive('warning')->once()->with('Queue health requires attention.', \Mockery::on(
            fn ($context) => $context['oldest_ready_job_seconds'] === 0 && $context['failed_jobs_last_24_hours'] === 1
        ));
        $this->artisan('aadi:queue-health')->assertExitCode(1);
    }
}
