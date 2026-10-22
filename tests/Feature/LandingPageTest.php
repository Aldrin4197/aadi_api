<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Marketplace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_features_public_listings_across_categories_independently_of_map_filters(): void
    {
        $owner = User::factory()->create(['landlord_status'=>'approved', 'vehicle_owner_status'=>'approved', 'service_provider_status'=>'approved']);
        $market = app(Marketplace::class);
        foreach (array_keys(Marketplace::TYPES) as $service) {
            foreach (['active', 'draft', 'active', 'active'] as $index => $status) {
                $record = $market->save($owner, $service, [
                    'title'=>$service.' '.$index, 'description'=>'Fixture listing', 'status'=>$status,
                    'type'=>Marketplace::TYPES[$service][0], 'price'=>1200, 'photos'=>[],
                    'location'=>['lat'=>11.7753,'lng'=>124.8861,'city'=>'Catbalogan','address'=>'Catbalogan'],
                    'details'=>['brand'=>'Toyota','model'=>'Hiace','year'=>2024],
                ]);
                if ($index === 3) $record->delete();
            }
        }
        $this->withoutVite()->get('/?q=no-matching-record')->assertInertia(fn (Assert $page) => $page
            ->has('results.data', 0)->has('landing.featured', 6)
            ->where('landing.featured', fn ($items) => collect($items)->every(fn ($item) => $item['status'] === 'active')
                && collect($items)->groupBy('service')->map->count()->all() === ['spaces'=>2,'haul'=>2,'raketeer'=>2]));
        $this->withoutVite()->get('/explore')->assertInertia(fn (Assert $page) => $page->missing('landing'));
    }

    public function test_missing_reviews_and_store_links_have_honest_empty_states(): void
    {
        config(['aadi_landing.reviews'=>[], 'aadi_landing.google_play_url'=>null, 'aadi_landing.app_store_url'=>null]);
        $this->withoutVite()->get('/')->assertInertia(fn (Assert $page) => $page
            ->has('landing.featured', 0)->has('landing.reviews', 0)
            ->where('landing.downloads.android', null)->where('landing.downloads.ios', null));
    }

    public function test_only_published_sourced_reviews_and_official_https_store_links_are_exposed(): void
    {
        // Test-only text, never seeded or published to the homepage configuration.
        $review = ['published'=>true, 'quote'=>'Fixture quote', 'name'=>'Fixture author', 'source_url'=>'https://example.org/review'];
        config([
            'aadi_landing.reviews'=>[$review, [...$review, 'published'=>false], [...$review, 'source_url'=>'javascript:alert(1)']],
            'aadi_landing.google_play_url'=>'https://play.google.com/store/apps/details?id=test.fixture',
            'aadi_landing.app_store_url'=>'https://apps.apple.com.untrusted.example/app/test',
        ]);
        $this->withoutVite()->get('/')->assertInertia(fn (Assert $page) => $page
            ->has('landing.reviews', 1)->where('landing.reviews.0.name', 'Fixture author')
            ->where('landing.downloads.android', 'https://play.google.com/store/apps/details?id=test.fixture')
            ->where('landing.downloads.ios', null));
    }
}
