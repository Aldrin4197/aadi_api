<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConfirmEmailMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirmation_email_is_branded_and_links_to_the_signed_verification_route(): void
    {
        $user = User::factory()->unverified()->create(['name' => 'Maria Santos']);

        $mail = (new VerifyEmail)->toMail($user);
        $rendered = (string) $mail->render();

        $this->assertSame('Confirm your Aadi email address', $mail->subject);
        $this->assertSame('Confirm email address', $mail->actionText);
        $this->assertStringContainsString('/verify-email/'.$user->id.'/'.sha1($user->email), $mail->actionUrl);
        $this->assertStringContainsString('signature=', $mail->actionUrl);
        $this->assertStringContainsString('Hi Maria Santos', $rendered);
        $this->assertStringContainsString('expires in 60 minutes', $rendered);
        $this->assertStringContainsString('The Aadi team', $rendered);
    }

    public function test_mail_test_command_refuses_to_run_without_an_smtp_password(): void
    {
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.password' => null]);

        $this->artisan('aadi:mail-test', ['to' => 'you@example.com'])
            ->expectsOutputToContain('MAIL_PASSWORD is empty')
            ->assertFailed();
    }

    public function test_mail_test_command_sends_with_the_array_mailer(): void
    {
        config(['mail.default' => 'array']);

        $this->artisan('aadi:mail-test', ['to' => 'you@example.com'])->expectsOutputToContain('Sent.')->assertSuccessful();
        $this->artisan('aadi:mail-test', ['to' => 'you@example.com', '--verification' => true])->expectsOutputToContain('Sent.')->assertSuccessful();
    }
}
