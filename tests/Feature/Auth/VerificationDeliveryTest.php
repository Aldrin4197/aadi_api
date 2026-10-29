<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VerificationDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_registration_sends_verification(): void
    {
        Notification::fake();
        $this->post('/register', ['name'=>'New User','email'=>'new@example.com','password'=>'password','password_confirmation'=>'password'])->assertRedirect(route('verification.notice'));
        Notification::assertSentTo(User::where('email','new@example.com')->firstOrFail(), VerifyEmail::class);
    }

    public function test_mobile_registration_and_resend_share_verification_status(): void
    {
        Notification::fake();
        $this->postJson('/api/auth/register', ['name'=>'Mobile User','email'=>'mobile@example.com','password'=>'password'])->assertCreated()->assertJsonPath('user.emailVerified', false)->assertJsonPath('verificationEmailSent', true);
        $user=User::where('email','mobile@example.com')->firstOrFail();
        Notification::assertSentTo($user, VerifyEmail::class);
        Sanctum::actingAs($user);
        $this->getJson('/api/v1/email-verification')->assertOk()->assertJsonPath('emailVerified',false);
        $this->postJson('/api/v1/email-verification/resend')->assertOk();
    }

    public function test_expired_or_another_users_link_cannot_verify(): void
    {
        $user=User::factory()->unverified()->create();
        $other=User::factory()->unverified()->create();
        $expired=URL::temporarySignedRoute('verification.verify',now()->subMinute(),['id'=>$user->id,'hash'=>sha1($user->email)]);
        $this->actingAs($user)->get($expired)->assertForbidden();
        $valid=URL::temporarySignedRoute('verification.verify',now()->addHour(),['id'=>$user->id,'hash'=>sha1($user->email)]);
        $this->actingAs($other)->get($valid)->assertForbidden();
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_changed_email_invalidates_old_link(): void
    {
        $user=User::factory()->unverified()->create();
        $url=URL::temporarySignedRoute('verification.verify',now()->addHour(),['id'=>$user->id,'hash'=>sha1($user->email)]);
        $user->update(['email'=>'changed@example.com']);
        $this->actingAs($user)->get($url)->assertForbidden();
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }
}
