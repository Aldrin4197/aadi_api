<?php
namespace Tests\Feature;
use App\Models\User;
use App\Services\Marketplace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
class PublicProviderProfileTest extends TestCase {
    use RefreshDatabase;
    public function test_public_profile_excludes_private_fields_and_drafts(): void {
        $user = User::factory()->create(['landlord_status'=>'approved','bio'=>'Local host','city'=>'Catbalogan','address'=>'Private address','phone'=>'09123456789']);
        $data = ['title'=>'Published space','description'=>'Private full listing text','type'=>'apartment','price'=>1000,'status'=>'active','photos'=>[], 'location'=>['lat'=>11.77,'lng'=>124.88,'city'=>'Catbalogan','address'=>'Exact address']];
        app(Marketplace::class)->save($user, 'spaces', $data);
        app(Marketplace::class)->save($user, 'spaces', [...$data,'title'=>'Draft listing','status'=>'draft']);
        $response = $this->getJson('/api/v1/providers/'.$user->id)->assertOk()->assertJsonPath('provider.name',$user->name)->assertJsonPath('counts.spaces',1)->assertJsonCount(1,'results.data');
        foreach (['email','phone','address','documents','avatar_path'] as $key) $this->assertArrayNotHasKey($key,$response->json('provider'));
        $this->assertArrayNotHasKey('description',$response->json('results.data.0'));
        $this->assertArrayNotHasKey('lat',$response->json('results.data.0.location'));
        $this->getJson('/api/v1/providers/'.$user->id.'?service=invalid')->assertUnprocessable();
    }
    public function test_avatar_is_served_without_a_storage_link_and_old_photo_is_unavailable(): void {
        Storage::fake('public');$user=User::factory()->create();
        $this->actingAs($user)->post('/profile/avatar',['avatar'=>UploadedFile::fake()->image('avatar.jpg')])->assertRedirect();
        $first=$user->fresh()->avatar_url;
        $this->get($first)->assertOk()->assertHeader('X-Content-Type-Options','nosniff');
        $this->post('/profile/avatar',['avatar'=>UploadedFile::fake()->image('next.jpg')])->assertRedirect();
        $this->get($first)->assertNotFound();
        $this->get($user->fresh()->avatar_url)->assertOk();
        $this->get('/avatars/'.$user->id.'/private.pdf')->assertNotFound();
    }
}
