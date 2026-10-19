<?php
namespace Tests\Feature;
use App\Models\{User, ProviderApplication};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProviderApplicationTest extends TestCase {
    use RefreshDatabase;
    protected function setUp(): void { parent::setUp(); Storage::fake('local'); $this->withoutVite(); }
    private function details(): array {
        return ['displayName'=>'Neighborhood services', 'phone'=>'09171234567', 'area'=>'Catbalogan',
            'description'=>'I offer repairs and property services nearby.',
            'documents'=>[UploadedFile::fake()->image('identity.jpg')], 'confirmed'=>true];
    }
    private function administrator(): User {
        $user=User::factory()->create(); $user->forceFill(['is_admin'=>true])->save(); return $user;
    }
    public function test_user_can_browse_and_submit_independent_role_applications(): void {
        $user=User::factory()->create();
        $this->actingAs($user)->get('/dashboard')->assertRedirect('/');
        $this->get('/become-provider')->assertOk();
        foreach (['spaces','haul','raketeer'] as $role) {
            $this->post('/provider-applications/'.$role,$this->details())->assertRedirect('/provider-applications/'.$role);
        }
        $this->assertDatabaseCount('provider_applications',3);
        $this->assertSame('pending',$user->fresh()->landlord_status->value);
        $this->assertSame('pending',$user->fresh()->vehicle_owner_status->value);
        $this->get('/my-listings')->assertRedirect('/become-provider');
    }
    public function test_documents_are_private_and_duplicates_do_not_create_files(): void {
        $owner=User::factory()->create();
        $this->actingAs($owner)->post('/provider-applications/spaces',$this->details())->assertRedirect();
        $application=ProviderApplication::firstOrFail(); $document=$application->documents[0];
        Storage::disk('local')->assertExists($document['path']);
        $this->assertArrayNotHasKey('path',$application->toReviewData()['documents'][0]);
        $url='/provider-documents/'.$application->id.'/0';
        $this->get($url)->assertOk();
        $this->post('/provider-applications/spaces',$this->details())->assertSessionHasErrors('application');
        $this->assertCount(1,Storage::disk('local')->allFiles());
        $this->actingAs(User::factory()->create())->get($url)->assertForbidden();
        $this->actingAs($this->administrator())->get($url)->assertOk();
    }
    public function test_approval_unlocks_only_the_requested_role(): void {
        $owner=User::factory()->create();
        $this->actingAs($owner)->post('/provider-applications/spaces',$this->details());
        $this->actingAs($this->administrator())->post('/admin/approvals/'.$owner->id.'/spaces',['status'=>'approved'])->assertRedirect();
        $this->assertSame('approved',$owner->fresh()->landlord_status->value);
        $this->assertSame('none',$owner->fresh()->vehicle_owner_status->value);
        $this->actingAs($owner->fresh())->get('/my-listings/spaces/new')->assertOk();
        $this->get('/my-listings/haul/new')->assertRedirect('/provider-applications/haul');
    }
    public function test_rejection_has_a_reason_and_resubmission_replaces_documents(): void {
        $owner=User::factory()->create();
        $this->actingAs($owner)->post('/provider-applications/raketeer',$this->details());
        $oldPath=ProviderApplication::firstOrFail()->documents[0]['path'];
        $this->actingAs($this->administrator())->post('/admin/approvals/'.$owner->id.'/raketeer',['status'=>'rejected'])->assertSessionHasErrors('reviewNote');
        $this->post('/admin/approvals/'.$owner->id.'/raketeer',['status'=>'rejected','reviewNote'=>'Please provide a clearer document.'])->assertRedirect();
        $this->assertDatabaseHas('provider_applications',['status'=>'rejected','review_note'=>'Please provide a clearer document.']);
        $this->actingAs($owner->fresh())->post('/provider-applications/raketeer',$this->details())->assertRedirect();
        $this->assertDatabaseHas('provider_applications',['status'=>'pending','review_note'=>null]);
        Storage::disk('local')->assertMissing($oldPath);
        $this->assertDatabaseCount('provider_applications',1);
    }
    public function test_evidence_and_confirmation_are_required_and_non_admin_cannot_review(): void {
        $user=User::factory()->create();
        $this->actingAs($user)->post('/provider-applications/haul',[])->assertSessionHasErrors(['displayName','documents','confirmed']);
        $this->get('/admin/approvals')->assertForbidden();
        $this->post('/admin/approvals/'.$user->id.'/haul',['status'=>'approved'])->assertForbidden();
        $this->get('/provider-applications/unknown')->assertNotFound();
    }
}
