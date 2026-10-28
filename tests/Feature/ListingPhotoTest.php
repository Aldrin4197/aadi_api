<?php
namespace Tests\Feature;
use App\Models\{User,Listing,VehicleListing,ServiceListing};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
class ListingPhotoTest extends TestCase {
    use RefreshDatabase;
    public function test_upload_is_readable_without_storage_symlink_and_legacy_urls_resolve(): void {
        Storage::fake('public');$user=User::factory()->create();
        $response=$this->actingAs($user)->post('/listing-photos',['photo'=>UploadedFile::fake()->image('photo.jpg')])->assertCreated();
        $url=$response->json('url');
        $this->assertStringContainsString('/media/listing-photos/'.$user->id.'/',$url);
        $this->get($url)->assertOk()->assertHeader('Content-Type','image/jpeg')->assertHeader('X-Content-Type-Options','nosniff');
        $path=Storage::disk('public')->allFiles()[0];
        $legacy='http://old-host.invalid/storage/'.$path;
        $external='https://example.com/external.jpg';
        foreach ([[new Listing,'photos'],[new VehicleListing,'images'],[new ServiceListing,'images']] as [$record,$field]) {
            $record->setRawAttributes([$field=>json_encode([$legacy,$external])], true);
            $this->assertSame([$url,$external],$record->{$field});
            $this->assertSame([$url,$external],$record->toArray()[$field]);
            $this->assertSame(json_encode([$legacy,$external]),$record->getRawOriginal($field));
        }
        $this->get('/media/listing-photos/'.$user->id.'/missing.jpg')->assertNotFound();
        Storage::disk('public')->put('listing-photos/'.$user->id.'/fake.jpg','not an image');
        $this->get('/media/listing-photos/'.$user->id.'/fake.jpg')->assertNotFound();
        $this->get('/media/listing-photos/'.$user->id.'/private.pdf')->assertNotFound();
    }
    public function test_guest_upload_is_rejected(): void {
        $this->postJson('/listing-photos')->assertUnauthorized();
    }
}
