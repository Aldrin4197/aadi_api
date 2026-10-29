<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SocialLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Http::preventStrayRequests();
        foreach (['google', 'facebook'] as $provider) config(["social_login.$provider"=>[
            'client_id'=>'test-client', 'client_secret'=>'test-secret',
            'redirect_uri'=>"http://localhost/auth/$provider/callback", 'version'=>'v25.0',
        ]]);
    }

    private function flow(string $provider = 'google'): array
    {
        return ['social_login'=>['state'=>'expected-state', 'provider'=>$provider, 'verifier'=>'test-verifier', 'expires'=>time()+600]];
    }

    private function google(string $email = 'new@gmail.com', bool $verified = true): void
    {
        Http::fake([
            'oauth2.googleapis.com/token'=>Http::response(['access_token'=>'server-token']),
            'openidconnect.googleapis.com/v1/userinfo'=>Http::response(['sub'=>'google-123', 'email'=>$email, 'email_verified'=>$verified, 'name'=>'New User']),
        ]);
    }

    public function test_redirect_creates_state_and_pkce(): void
    {
        $response = $this->get('/auth/google/redirect')->assertRedirect();
        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->assertSame('S256', $query['code_challenge_method']);
        $this->assertSame(session('social_login.state'), $query['state']);
        $this->assertArrayNotHasKey('client_secret', $query);
    }

    public function test_bad_state_never_calls_provider(): void
    {
        $this->withSession($this->flow())->get('/auth/google/callback?state=wrong&code=code')->assertRedirect('/login');
        Http::assertNothingSent();
        $this->assertGuest();
    }

    public function test_google_creates_verified_account_and_consumes_state(): void
    {
        $this->google();
        $this->withSession($this->flow())->get('/auth/google/callback?state=expected-state&code=code')->assertRedirect('/dashboard');
        $user = User::where('email', 'new@gmail.com')->firstOrFail();
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('social_accounts', ['user_id'=>$user->id, 'provider'=>'google', 'provider_id'=>'google-123']);
        $this->assertNull(session('social_login'));
        Notification::assertNothingSent();
    }

    public function test_existing_email_is_not_automatically_linked(): void
    {
        $user = User::factory()->create(['email'=>'existing@gmail.com']);
        $this->google('existing@gmail.com');
        $this->withSession($this->flow())->get('/auth/google/callback?state=expected-state&code=code')->assertRedirect('/login');
        $this->assertGuest();
        $this->assertDatabaseCount('social_accounts', 0);
        $this->assertSame($user->id, User::where('email','existing@gmail.com')->first()->id);
    }

    public function test_returning_identity_uses_provider_id_without_overwriting_email(): void
    {
        $user = User::factory()->create(['email'=>'chosen@example.com']);
        DB::table('social_accounts')->insert(['user_id'=>$user->id,'provider'=>'google','provider_id'=>'google-123']);
        $this->google('changed@gmail.com');
        $this->withSession($this->flow())->get('/auth/google/callback?state=expected-state&code=code')->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->assertSame('chosen@example.com', $user->fresh()->email);
    }

    public function test_facebook_does_not_grant_email_verification(): void
    {
        Http::fake([
            'graph.facebook.com/*/oauth/access_token'=>Http::response(['access_token'=>'server-token']),
            'graph.facebook.com/*/me*'=>Http::response(['id'=>'fb-123','email'=>'facebook@example.com','name'=>'Facebook User']),
        ]);
        $this->withSession($this->flow('facebook'))->get('/auth/facebook/callback?state=expected-state&code=code')->assertRedirect(route('verification.notice'));
        $user=User::where('email','facebook@example.com')->firstOrFail();
        $this->assertFalse($user->hasVerifiedEmail());
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_non_authoritative_google_address_requires_mail_verification(): void
    {
        $this->google('person@example.com');
        $this->withSession($this->flow())->get('/auth/google/callback?state=expected-state&code=code')->assertRedirect(route('verification.notice'));
        Notification::assertSentTo(User::where('email','person@example.com')->firstOrFail(), VerifyEmail::class);
    }

    public function test_unconfigured_provider_is_unavailable(): void
    {
        config(['social_login.google.client_secret'=>null]);
        $this->get('/auth/google/redirect')->assertNotFound();
    }
    public function test_connect_requires_password_confirmation(): void
    {
        $this->actingAs(User::factory()->create())->get('/settings/social/google/connect')->assertRedirect(route('password.confirm'));
        Http::assertNothingSent();
    }

    public function test_explicit_connect_preserves_email_and_verification(): void
    {
        $user=User::factory()->unverified()->create(['email'=>'original@example.com']);
        $flow=$this->flow();
        $flow['social_login']['user_id']=$user->id;
        $this->google();
        $this->actingAs($user)->withSession($flow)->get('/auth/google/callback?state=expected-state&code=code')->assertRedirect('/profile');
        $this->assertDatabaseHas('social_accounts',['user_id'=>$user->id,'provider'=>'google','provider_id'=>'google-123']);
        $this->assertSame('original@example.com',$user->fresh()->email);
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_connect_cannot_take_another_users_identity(): void
    {
        $owner=User::factory()->create();
        $user=User::factory()->create();
        DB::table('social_accounts')->insert(['user_id'=>$owner->id,'provider'=>'google','provider_id'=>'google-123']);
        $flow=$this->flow(); $flow['social_login']['user_id']=$user->id;
        $this->google();
        $this->actingAs($user)->withSession($flow)->get('/auth/google/callback?state=expected-state&code=code')->assertRedirect('/profile');
        $this->assertDatabaseCount('social_accounts',1);
        $this->assertDatabaseHas('social_accounts',['user_id'=>$owner->id,'provider_id'=>'google-123']);
    }

    public function test_changed_session_user_invalidates_connect(): void
    {
        $flow=$this->flow(); $flow['social_login']['user_id']=User::factory()->create()->id;
        $this->actingAs(User::factory()->create())->withSession($flow)->get('/auth/google/callback?state=expected-state&code=code')->assertRedirect('/profile');
        Http::assertNothingSent();
    }

    public function test_expired_state_is_rejected_before_provider_request(): void
    {
        $flow=$this->flow(); $flow['social_login']['expires']=time()-1;
        $this->withSession($flow)->get('/auth/google/callback?state=expected-state&code=code')->assertRedirect('/login');
        Http::assertNothingSent();
    }

}
