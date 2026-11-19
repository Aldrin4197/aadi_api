<?php

namespace Tests\Feature;

use App\Services\LandingPage;
use App\Services\Marketplace;
use App\Support\PublicPageSeo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\Request;
use Mockery;

class PublicSeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['app.url' => 'https://aadi.example']);
    }

    public function test_public_pages_have_server_rendered_metadata_and_filtered_pages_are_not_indexed(): void
    {
        $market = Mockery::mock(Marketplace::class);
        $market->shouldReceive('search')->andReturn(['data' => [], 'meta' => []]);
        $this->app->instance(Marketplace::class, $market);
        $landing = Mockery::mock(LandingPage::class);
        $landing->shouldReceive('content')->andReturn([]);
        $this->app->instance(LandingPage::class, $landing);

        $this->get('/', ['Host' => 'untrusted.example'])->assertOk()
            ->assertSee('<title inertia>Find properties, vehicles &amp; rakets nearby — Aadi</title>', false)
            ->assertSee('name="robots" content="index, follow"', false)
            ->assertSee('rel="canonical" href="https://aadi.example/"', false)
            ->assertSee('property="og:image" content="https://aadi.example/images/logos/Aadi_Color_w_tagname.png"', false)
            ->assertSee('JavaScript is required');
        $this->get('/explore?service=haul')->assertOk()
            ->assertSee('name="robots" content="noindex, follow"', false)
            ->assertSee('rel="canonical" href="https://aadi.example/explore"', false);
    }

    public function test_profiles_and_private_pages_are_not_indexable(): void
    {
        foreach (['/providers/example', '/offers/spaces/example', '/login', '/dashboard'] as $path) {
            $seo = PublicPageSeo::forRequest(Request::create($path));
            $this->assertSame('noindex, nofollow', $seo['robots']);
            $this->assertNull($seo['canonical']);
        }

        $this->get('/login')->assertOk()->assertSee('name="robots" content="noindex, nofollow"', false);
    }

    public function test_legal_pages_are_public_and_have_their_own_metadata_and_content(): void
    {
        foreach ([
            '/privacy-policy' => ['Privacy Policy', 'privacy', 'public-privacy.txt'],
            '/terms-of-service' => ['Terms of Service', 'terms', 'public-terms.txt'],
            '/data-deletion' => ['Data Deletion Instructions', 'data-deletion', 'public-data-deletion.txt'],
        ] as $path => [$title, $kind, $file]) {
            $content = file_get_contents(resource_path('legal/'.$file));
            $this->get($path)->assertOk()
                ->assertSee('<title inertia>'.$title.' — Aadi</title>', false)
                ->assertSee('rel="canonical" href="https://aadi.example'.$path.'"', false)
                ->assertSee('name="robots" content="index, follow"', false)
                ->assertSee('Effective date: November 30, 2026')
                ->assertSee(htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false), false)
                ->assertInertia(fn ($page) => $page->component('Legal/PublicDocument')
                    ->where('document.kind', $kind)
                    ->where('document.content', $content));
        }
    }

    public function test_sitemap_and_robots_only_advertise_public_pages(): void
    {
        $this->get('/robots.txt')->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('Sitemap: https://aadi.example/sitemap.xml');
        $response = $this->get('/sitemap.xml')->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $xml = simplexml_load_string($response->getContent());
        $this->assertNotFalse($xml);
        $this->assertSame(['https://aadi.example/', 'https://aadi.example/explore', 'https://aadi.example/privacy-policy', 'https://aadi.example/terms-of-service', 'https://aadi.example/data-deletion'], array_map(
            fn ($url) => (string) $url->loc,
            iterator_to_array($xml->url, false),
        ));
    }
}
