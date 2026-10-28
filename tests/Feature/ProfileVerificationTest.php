<?php
namespace Tests\Feature;
use App\Models\{User, ProviderApplication};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
class ProfileVerificationTest extends TestCase {
    use RefreshDatabase;
    private function application(array $extra = []): array {
        return array_replace([
            'action'=>'submit','displayName'=>'Local rentals','phone'=>'09123456789','area'=>'Catbalogan',
            'description'=>'Rental vehicles for local deliveries and moving.',
            'details'=>['applicantType'=>'individual'], 'confirmed'=>true,
            'documents'=>[UploadedFile::fake()->create('identity.pdf',10,'application/pdf'),UploadedFile::fake()->create('or.pdf',10,'application/pdf'),UploadedFile::fake()->create('cr.pdf',10,'application/pdf')],
            'documentMeta'=>[['type'=>'identity'],['type'=>'vehicle_or','reference'=>'Test van'],['type'=>'vehicle_cr','reference'=>'Test van']],
        ], $extra);
    }
    public function test_avatar_replace_remove_and_private_profile_fields(): void {
        Storage::fake('public');$user=User::factory()->create();
        $this->actingAs($user)->post('/profile/avatar',['avatar'=>UploadedFile::fake()->image('avatar.jpg',600,600)])->assertRedirect();
        $first=$user->fresh()->avatar_path;Storage::disk('public')->assertExists($first);
        $this->post('/profile/avatar',['avatar'=>UploadedFile::fake()->image('next.png',512,512)])->assertRedirect();
        Storage::disk('public')->assertMissing($first);$second=$user->fresh()->avatar_path;
        $this->patch('/profile',['name'=>$user->name,'email'=>$user->email,'phone'=>'09123456789','bio'=>'Local repair specialist','city'=>'Catbalogan','address'=>'Private address','is_admin'=>true])->assertRedirect();
        $this->assertSame('Private address',$user->fresh()->address);$this->assertFalse($user->fresh()->is_admin);
        $this->assertArrayNotHasKey('address',$user->fresh()->toArray());
        $this->delete('/profile/avatar')->assertRedirect();Storage::disk('public')->assertMissing($second);$this->assertNull($user->fresh()->avatar_url);
        $this->post('/profile/avatar',['avatar'=>UploadedFile::fake()->create('bad.svg',1,'image/svg+xml')])->assertSessionHasErrors('avatar');
    }
    public function test_documents_are_private_typed_and_reviewed_separately(): void {
        Storage::fake('local');Storage::fake('public');$owner=User::factory()->create();$other=User::factory()->create();
        $this->actingAs($owner)->post('/provider-applications/haul',$this->application())->assertRedirect();
        $app=ProviderApplication::firstOrFail();$this->assertSame('pending',$app->status);$this->assertSame('pending',$owner->fresh()->vehicle_owner_status->value);
        $this->assertSame('vehicle_or',$app->documents[1]['type']);Storage::disk('local')->assertExists($app->documents[0]['path']);
        $this->assertArrayNotHasKey('path',$app->toReviewData()['documents'][0]);
        $this->get('/provider-documents/'.$app->id.'/0')->assertOk()->assertHeader('Cache-Control','no-store, private');
        $this->actingAs($other)->get('/provider-documents/'.$app->id.'/0')->assertForbidden();
        $this->post('/admin/approvals/'.$owner->id.'/haul',['status'=>'approved','version'=>$app->fresh()->revision()])->assertForbidden();
        $admin=User::factory()->create();$admin->forceFill(['is_admin'=>true])->save();
        $this->actingAs($admin)->post('/admin/approvals/'.$owner->id.'/haul',['status'=>'approved','version'=>$app->fresh()->revision()])->assertRedirect();
        $this->assertSame('approved',$owner->fresh()->vehicle_owner_status->value);
        $this->assertSame('none',$owner->fresh()->landlord_status->value);
    }
    public function test_drafts_missing_requirements_expiry_and_resubmission(): void {
        Storage::fake('local');$owner=User::factory()->create();
        $this->actingAs($owner)->post('/provider-applications/haul',$this->application(['action'=>'draft','documents'=>[],'documentMeta'=>[],'confirmed'=>false]))->assertRedirect();
        $app=ProviderApplication::firstOrFail();$this->assertSame('draft',$app->status);$this->assertSame('none',$owner->fresh()->vehicle_owner_status->value);
        $this->post('/provider-applications/haul',$this->application(['version'=>$app->fresh()->revision(),'documents'=>[],'documentMeta'=>[]]))->assertSessionHasErrors('documents');
        $this->post('/provider-applications/haul',$this->application(['version'=>$app->fresh()->revision(),'documentMeta'=>[['type'=>'identity'],['type'=>'vehicle_or','expiresAt'=>'2000-01-01'],['type'=>'vehicle_cr']]]))->assertSessionHasErrors('documents');
        $this->assertCount(0,Storage::disk('local')->allFiles());
        $this->post('/provider-applications/haul',$this->application(['version'=>$app->fresh()->revision()]))->assertRedirect();
        $app->refresh();$files=$app->documents;
        $admin=User::factory()->create();$admin->forceFill(['is_admin'=>true])->save();
        $this->actingAs($admin)->post('/admin/approvals/'.$owner->id.'/haul',['status'=>'rejected','version'=>$app->fresh()->revision(),'reviewNote'=>'Clarify your service area.'])->assertRedirect();
        $this->actingAs($owner)->post('/provider-applications/haul',$this->application(['version'=>$app->fresh()->revision(),'documents'=>[],'documentMeta'=>[],'keepDocuments'=>[0,1,2]]))->assertRedirect();
        $this->assertSame($files,$app->fresh()->documents);
        $this->post('/provider-applications/haul',$this->application())->assertSessionHasErrors('application');
    }
    public function test_versioned_api_returns_private_metadata_and_rejects_stale_drafts(): void {
        Storage::fake('local');$owner=User::factory()->create();
        \Laravel\Sanctum\Sanctum::actingAs($owner);
        $this->post('/api/v1/provider-applications/haul',$this->application(),['Accept'=>'application/json'])->assertCreated()
            ->assertJsonPath('data.documents.0.type','identity');
        $app=ProviderApplication::firstOrFail();
        $this->getJson('/api/v1/provider-applications/haul')->assertOk()
            ->assertJsonPath('data.application.documents.0.url','/api/v1/provider-documents/'.$app->id.'/0')
            ->assertJsonPath('data.requirements.vehicle_cr.required',true);
        $this->get('/api/v1/provider-documents/'.$app->id.'/0')->assertOk();
        $this->getJson('/api/v1/profile')->assertOk()->assertJsonPath('data.id',$owner->id);
        $app->update(['status'=>'draft']);$owner->update(['vehicle_owner_status'=>'none']);
        $this->post('/api/v1/provider-applications/haul',$this->application(['action'=>'draft','version'=>str_repeat('0',64),'documents'=>[],'documentMeta'=>[]]),['Accept'=>'application/json'])
            ->assertUnprocessable()->assertJsonValidationErrors('application');
        $this->assertCount(3,Storage::disk('local')->allFiles());
    }

}
