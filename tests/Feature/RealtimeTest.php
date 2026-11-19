<?php

namespace Tests\Feature;

use App\Events\UserActivityChanged;
use App\Http\Middleware\EnsureLegalAcceptance;
use App\Models\User;
use App\Notifications\ActivityEmail;
use App\Notifications\AppNotification;
use App\Services\RealtimePublisher;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Mockery;

class RealtimeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'broadcasting.default' => 'pusher',
            'broadcasting.connections.pusher.key' => 'test-key',
            'broadcasting.connections.pusher.secret' => 'test-secret',
            'broadcasting.connections.pusher.app_id' => '123',
            'broadcasting.connections.pusher.options.cluster' => 'ap1',
        ]);
        require base_path('routes/channels.php');
    }

    public function test_private_channels_only_authorize_their_own_signed_in_user(): void
    {
        $this->withoutMiddleware(EnsureLegalAcceptance::class);
        $user = (new User)->forceFill(['id' => '01J00000000000000000000001', 'birth_date' => '1990-01-01', 'sex_assigned_at_birth' => 'prefer_not_to_say', 'city' => 'Catbalogan', 'address' => 'Private address']);
        $auth = ['socket_id' => '123.456', 'channel_name' => 'private-users.'.$user->id];

        $this->postJson('/broadcasting/auth', $auth)->assertUnauthorized();
        $this->actingAs($user)->postJson('/broadcasting/auth', $auth)->assertOk()->assertJsonStructure(['auth']);
        $this->postJson('/broadcasting/auth', [...$auth, 'channel_name' => 'private-users.01J00000000000000000000002'])->assertForbidden();
        $user->forceFill(['restriction_type' => 'disabled']);
        $this->postJson('/broadcasting/auth', $auth)->assertForbidden();
    }

    public function test_activity_is_queued_only_after_commit_with_no_message_content(): void
    {
        Queue::fake();
        $callback = null;
        DB::shouldReceive('afterCommit')->once()->andReturnUsing(function ($fn) use (&$callback) { $callback = $fn; });

        app(RealtimePublisher::class)->publish('01J00000000000000000000001', 'message');
        Queue::assertNothingPushed();
        $this->assertNotNull($callback);
        $callback();

        Queue::assertPushed(BroadcastEvent::class, function (BroadcastEvent $job) {
            $event = $job->event;
            return $event instanceof UserActivityChanged
                && $event->broadcastAs() === 'activity.changed'
                && $event->broadcastWith() === ['type' => 'message']
                && $event->broadcastOn()[0]->name === 'private-users.01J00000000000000000000001';
        });
    }

    public function test_rolled_back_activity_is_not_queued(): void
    {
        if (!in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('The SQLite PDO extension is required.');
        }

        Queue::fake();
        DB::beginTransaction();
        app(RealtimePublisher::class)->publish('user-1', 'message');
        DB::rollBack();
        Queue::assertNothingPushed();

        DB::beginTransaction();
        app(RealtimePublisher::class)->publish('user-1', 'message');
        Queue::assertNothingPushed();
        DB::commit();
        Queue::assertPushed(BroadcastEvent::class);
    }

    public function test_notifications_trigger_the_correct_refresh_only_after_database_delivery(): void
    {
        $publisher = Mockery::mock(RealtimePublisher::class);
        $publisher->shouldReceive('publish')->once()->with('user-1', 'message');
        $publisher->shouldReceive('publish')->once()->with('user-1', 'notification');
        $this->app->instance(RealtimePublisher::class, $publisher);
        $user = (new User)->forceFill(['id' => 'user-1']);

        (new AppNotification('message', 'New message'))->afterSending($user, 'database', null);
        (new AppNotification('request', 'New request'))->afterSending($user, 'database', null);
        (new AppNotification('message', 'New message'))->afterSending($user, 'mail', null);
    }

    public function test_notification_email_is_queued_only_after_commit_and_not_after_rollback(): void
    {
        if (!in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('The SQLite PDO extension is required.');
        }

        config(['mail.activity_notifications' => true, 'broadcasting.default' => 'log']);
        Queue::fake();
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->string('notifiable_type');
            $table->string('notifiable_id');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
        $user = (new User)->forceFill(['id' => 'user-1', 'email' => 'test@example.test']);
        $notification = new AppNotification('message', 'New message', 'Private text');

        DB::beginTransaction();
        $user->notify($notification);
        $this->assertSame(1, DB::table('notifications')->count());
        Queue::assertNothingPushed();
        DB::rollBack();
        $this->assertSame(0, DB::table('notifications')->count());
        Queue::assertNothingPushed();

        DB::beginTransaction();
        $user->notify($notification);
        Queue::assertNothingPushed();
        DB::commit();
        $this->assertSame(1, DB::table('notifications')->count());
        Queue::assertPushed(SendQueuedNotifications::class, fn ($job) =>
            $job->notification instanceof ActivityEmail && $job->channels === ['mail']);
    }

    public function test_notification_email_can_be_disabled_and_does_not_include_private_content(): void
    {
        config(['mail.activity_notifications' => false, 'broadcasting.default' => 'log']);
        Queue::fake();
        $user = (new User)->forceFill(['id' => 'user-1', 'email' => 'test@example.test']);
        (new AppNotification('message', 'Private title', 'Private message'))->afterSending($user, 'database', null);
        Queue::assertNothingPushed();

        $mail = (new ActivityEmail)->toMail($user);
        $this->assertSame('New Aadi notification', $mail->subject);
        $this->assertStringNotContainsString('Private', implode(' ', [...$mail->introLines, ...$mail->outroLines]));
    }

    public function test_database_notification_delivers_one_generic_email(): void
    {
        if (!in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('The SQLite PDO extension is required.');
        }

        config(['mail.activity_notifications' => true, 'mail.default' => 'array', 'queue.default' => 'sync', 'broadcasting.default' => 'log']);
        Schema::create('users', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('email');
            $table->timestamps();
        });
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->string('notifiable_type');
            $table->string('notifiable_id');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
        $user = (new User)->forceFill(['id' => 'user-1', 'email' => 'test@example.test']);
        $user->save();
        $user->notify(new AppNotification('message', 'New message', 'Private message text'));

        $messages = Mail::mailer('array')->getSymfonyTransport()->messages();
        $this->assertCount(1, $messages);
        $message = $messages->first()->getOriginalMessage();
        $this->assertSame('New Aadi notification', $message->getSubject());
        $this->assertSame('test@example.test', $message->getTo()[0]->getAddress());
        $this->assertStringNotContainsString('Private message text', $message->toString());
    }

    public function test_client_configuration_never_includes_the_secret_and_requires_complete_settings(): void
    {
        $this->assertSame(['key' => 'test-key', 'cluster' => 'ap1'], RealtimePublisher::clientConfig());
        config(['broadcasting.connections.pusher.secret' => null]);
        $this->assertNull(RealtimePublisher::clientConfig());
        config(['broadcasting.default' => 'log']);
        $this->assertNull(RealtimePublisher::clientConfig());
    }
}
