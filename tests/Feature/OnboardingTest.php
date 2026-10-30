<?php
namespace Tests\Feature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;
    private function answers(): array {
        return ['action'=>'save','personas'=>['student','freelancer'],'interests'=>['spaces','raketeer'],'city'=>'Catbalogan','address'=>'Private neighborhood','budget_min'=>1000,'budget_max'=>5000,'budget_unit'=>'month'];
    }
    public function test_guest_cannot_save_preferences(): void {
        $this->put('/account/onboarding',$this->answers())->assertRedirect('/login');
    }
    public function test_answers_belong_only_to_the_authenticated_user_and_do_not_grant_roles(): void {
        $user=User::factory()->create(); $other=User::factory()->create();
        $this->assertSame('pending',$user->fresh()->onboarding_status);
        $this->actingAs($user)->from('/dashboard')->put('/account/onboarding',$this->answers()+['user_id'=>$other->id,'is_admin'=>true,'landlord_status'=>'approved'])->assertRedirect('/dashboard');
        $user->refresh();
        $this->assertSame('completed',$user->onboarding_status);
        $this->assertSame('Private neighborhood',$user->onboarding_data['address']);
        $this->assertSame('none',$user->landlord_status->value);
        $this->assertFalse($user->is_admin);
        $this->assertNull($other->fresh()->onboarding_data);
        $this->assertArrayNotHasKey('onboarding_data',$user->toArray());
    }
    public function test_invalid_range_and_unknown_personas_are_rejected(): void {
        $this->actingAs(User::factory()->create())->put('/account/onboarding',array_replace($this->answers(),['budget_min'=>9000,'personas'=>['administrator']]))->assertSessionHasErrors(['budget_max','personas.0']);
    }
    public function test_skip_does_not_store_invalid_answers_or_downgrade_completed_setup(): void {
        $user=User::factory()->create();
        $this->actingAs($user)->put('/account/onboarding',$this->answers())->assertRedirect();
        $this->put('/account/onboarding',['action'=>'skip','budget_min'=>-20])->assertRedirect();
        $this->assertSame('completed',$user->fresh()->onboarding_status);
        $this->assertEquals(1000,$user->fresh()->onboarding_data['budget_min']);
    }
    public function test_skip_and_resume_are_persisted(): void {
        $user=User::factory()->create();
        $this->actingAs($user)->put('/account/onboarding',array_replace($this->answers(),['action'=>'skip']))->assertRedirect();
        $this->assertSame('skipped',$user->fresh()->onboarding_status);
        $this->put('/account/onboarding',$this->answers())->assertRedirect();
        $this->assertSame('completed',$user->fresh()->onboarding_status);
    }
}
