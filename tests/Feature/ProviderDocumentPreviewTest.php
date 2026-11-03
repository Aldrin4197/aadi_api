<?php
namespace Tests\Feature;
use App\Models\{ProviderApplication,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
class ProviderDocumentPreviewTest extends TestCase {
    use RefreshDatabase;
    public function test_private_previews_and_admin_decisions_for_each_role(): void {
        Storage::fake('local');
        $admin=User::factory()->create();$admin->forceFill(['is_admin'=>true])->save();
        $other=User::factory()->create();
        foreach (['spaces'=>'landlord_status','haul'=>'vehicle_owner_status','raketeer'=>'service_provider_status'] as $service=>$field) {
            $owner=User::factory()->create();$owner->update([$field=>'pending']);
            $documents=[];
            foreach(config('provider_verification.'.$service) as $type=>$rule) {
                if(!$rule['required']) continue;
                $file=UploadedFile::fake()->image($type.'.png');
                $documents[]=['path'=>$file->store('provider-documents/'.$owner->id,'local'),'name'=>$type.'.png','type'=>$type];
            }
            $app=ProviderApplication::create(['user_id'=>$owner->id,'service'=>$service,'status'=>'pending','display_name'=>'Test provider','phone'=>'09123456789','area'=>'Catbalogan','description'=>'Provider application for review.','documents'=>$documents,'details'=>['applicantType'=>'individual'],'submitted_at'=>now()]);
            $url='/provider-documents/'.$app->id.'/0?preview=1';
            $this->actingAs($other)->get($url)->assertForbidden();
            $this->actingAs($owner)->get($url)->assertOk()->assertHeader('Content-Type','image/png')->assertHeader('Cache-Control','no-store, private');
            $this->actingAs($admin)->get($url)->assertOk()->assertHeader('X-Content-Type-Options','nosniff');
            $this->post('/admin/approvals/'.$owner->id.'/'.$service,['status'=>'approved','version'=>'stale'])->assertSessionHasErrors('version');
            $this->post('/admin/approvals/'.$owner->id.'/'.$service,['status'=>'approved','version'=>$app->revision()])->assertRedirect();
            $this->assertSame('approved',$owner->fresh()->$field->value);
            $this->assertSame($admin->id,$app->fresh()->reviewer_id);
        }
    }
    public function test_unsupported_preview_is_rejected_but_original_download_remains_private(): void {
        Storage::fake('local');$owner=User::factory()->create();
        Storage::disk('local')->put('provider-documents/test.html','<html><script>alert(1)</script></html>');
        $app=ProviderApplication::create(['user_id'=>$owner->id,'service'=>'spaces','status'=>'pending','display_name'=>'Test','phone'=>'09123456789','area'=>'City','description'=>'Legacy document','documents'=>[['path'=>'provider-documents/test.html','name'=>'legacy.html']],'submitted_at'=>now()]);
        $this->actingAs($owner)->get('/provider-documents/'.$app->id.'/0?preview=1')->assertStatus(415);
    }
}
