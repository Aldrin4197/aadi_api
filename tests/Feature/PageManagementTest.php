<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PageManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admins_can_manage_the_allowlisted_public_pages(): void
    {
        $this->withoutVite();
        $user = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user)->get('/admin/pages')->assertForbidden();
        $this->patch('/admin/pages/home', ['enabled' => false])->assertForbidden();

        $this->actingAs($admin)->get('/admin/pages')->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/Pages/Index')
                ->where('pages.0.enabled', true)->has('pages', 3));
        $this->patch('/admin/pages/home', ['enabled' => 'bad'])->assertSessionHasErrors('enabled');
        $this->patch('/admin/pages/login', ['enabled' => false])->assertNotFound();
        $this->patch('/admin/pages/home', ['enabled' => false])->assertSessionHasNoErrors();
        $this->get('/admin/pages')->assertInertia(fn ($page) => $page->where('pages.0.enabled', false));
    }

    public function test_disabled_pages_return_unavailable_and_can_be_reenabled(): void
    {
        $this->withoutVite();
        $admin = User::factory()->create(['is_admin' => true]);
        $provider = User::factory()->create();
        $this->actingAs($admin);
        foreach (['home', 'explore', 'providers'] as $key) {
            $this->patch("/admin/pages/{$key}", ['enabled' => false])->assertSessionHasNoErrors();
        }
        foreach (['/', '/explore', "/providers/{$provider->id}"] as $path) {
            $this->get($path)->assertStatus(503)->assertSee('Page temporarily unavailable')
                ->assertHeader('Retry-After', '3600')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        }
        $version = app(\App\Http\Middleware\HandleInertiaRequests::class)->version(request());
        $this->get('/explore', ['X-Inertia' => 'true', 'X-Inertia-Version' => $version])->assertStatus(503)
            ->assertHeader('X-Inertia', 'true')->assertJsonPath('component', 'Error')
            ->assertJsonPath('props.title', 'Page temporarily unavailable');
        $this->getJson('/explore')->assertStatus(503)->assertJsonPath('message', 'This page is currently unavailable. Please try again later.');
        $this->getJson("/api/v1/providers/{$provider->id}")->assertOk();
        $this->get('/privacy-policy')->assertOk();
        $this->get('/admin/pages')->assertOk();
        $this->get('/sitemap.xml')->assertDontSee('<loc>'.config('app.url').'/</loc>', false)
            ->assertDontSee('<loc>'.config('app.url').'/explore</loc>', false);

        $this->patch('/admin/pages/providers', ['enabled' => true])->assertSessionHasNoErrors();
        $this->get("/providers/{$provider->id}")->assertOk();
    }

    public function test_web_errors_have_safe_pages_and_api_errors_remain_json(): void
    {
        config(['app.debug' => false]);
        foreach ([403, 419, 429, 500, 503] as $status) {
            Route::get("/test-error-{$status}", fn () => abort($status, 'private internal detail'));
        }
        $this->get('/no-such-page')->assertNotFound()->assertSee('Page not found');
        $this->get('/no-such-page', ['X-Inertia' => 'true'])->assertNotFound()
            ->assertHeader('X-Inertia', 'true')->assertJsonPath('component', 'Error');
        $this->getJson('/api/no-such-page')->assertNotFound()->assertHeader('Content-Type', 'application/json');
        foreach ([403, 419, 429, 500, 503] as $status) {
            $this->get("/test-error-{$status}")->assertStatus($status)
                ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
                ->assertDontSee('private internal detail');
        }
        $this->get('/test-error-500')->assertSee('Something went wrong');
    }
}
