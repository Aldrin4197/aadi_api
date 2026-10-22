<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Marketplace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MarketplaceLocationsTest extends TestCase
{
    use RefreshDatabase;

    private function listing(string $service, string $area, float $lat = 11.7753, string $status = 'active')
    {
        $owner = User::factory()->create(['landlord_status'=>'approved', 'vehicle_owner_status'=>'approved', 'service_provider_status'=>'approved']);
        return app(Marketplace::class)->save($owner, $service, [
            'title'=>'Find in '.$area, 'description'=>'Test listing', 'status'=>$status,
            'type'=>Marketplace::TYPES[$service][0], 'price'=>1200, 'photos'=>[],
            'location'=>['lat'=>$lat,'lng'=>124.8861,'city'=>$area,'address'=>$area],
            'details'=>['brand'=>'Toyota','model'=>'Hiace','year'=>2024],
        ]);
    }

    public function test_selected_area_coordinates_filter_results_and_survive_the_page_response(): void
    {
        $nearby = $this->listing('spaces', 'Catbalogan');
        $this->listing('spaces', 'Far away', 14.6);
        $query = http_build_query(['service'=>'spaces','lat'=>11.7753,'lng'=>124.8861,'radiusKm'=>5,'locationName'=>'Catbalogan']);
        $this->getJson('/api/v1/offers?'.$query)->assertOk()
            ->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.id', $nearby->id);
        $this->withoutVite()->get('/explore?'.$query)->assertInertia(fn (Assert $page) => $page
            ->where('filters.locationName', 'Catbalogan')->has('results.data', 1));
    }

    public function test_comparison_metadata_uses_saved_values_without_invented_inclusions(): void
    {
        $vehicle = $this->listing('haul', 'Catbalogan');
        $vehicle->update(['seating_capacity'=>7,'features'=>['Air conditioning']]);
        $this->getJson('/api/v1/offers?service=haul')->assertOk()
            ->assertJsonPath('data.0.comparison.scope', '7 seats')
            ->assertJsonPath('data.0.comparison.features.0', 'Air conditioning');
        $space = $this->listing('spaces', 'Catbalogan');
        $dto = app(Marketplace::class)->dto($space, 'spaces');
        $this->assertNull($dto['comparison']['scope']);
        $this->assertSame([], $dto['comparison']['features']);
    }
}
