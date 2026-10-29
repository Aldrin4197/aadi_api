<?php
namespace Tests\Feature;
use App\Models\User;
use App\Services\Marketplace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class ListingVerificationBadgeTest extends TestCase {
    use RefreshDatabase;
    public function test_email_verification_and_provider_approval_remain_independent(): void {
        $owner=User::factory()->create(['email_verified_at'=>null,'landlord_status'=>'approved','vehicle_owner_status'=>'approved','service_provider_status'=>'approved']);
        $market=app(Marketplace::class);
        foreach (['spaces','haul','raketeer'] as $service) {
            $record=$market->save($owner,$service,[
                'title'=>'Listing','description'=>'Test listing','type'=>Marketplace::TYPES[$service][0],'price'=>1000,'status'=>'draft','photos'=>[],
                'location'=>['lat'=>11.77,'lng'=>124.88,'city'=>'Catbalogan','address'=>'Address'],
                'details'=>['brand'=>'Toyota','model'=>'Hiace','year'=>2024],
            ]);
            $dto=$market->dto($record->fresh(),$service);
            $this->assertFalse($dto['ownerEmailVerified']);$this->assertTrue($dto['providerApproved']);
            $owner->forceFill(['email_verified_at'=>now(),$market->statusField($service)=>'pending'])->save();
            $dto=$market->dto($record->fresh(),$service);
            $this->assertTrue($dto['ownerEmailVerified']);$this->assertFalse($dto['providerApproved']);
            $owner->forceFill(['email_verified_at'=>null,$market->statusField($service)=>'approved'])->save();
        }
    }
}
