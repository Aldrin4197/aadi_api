<?php
namespace Tests\Feature;
use App\Models\User;
use App\Services\LegalDocuments;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LegalAcceptanceTest extends TestCase
{
    use RefreshDatabase;
    private string $bundlePath;
    protected function setUp(): void {
        parent::setUp(); $this->withoutVite();
        $bundle=app(LegalDocuments::class)->bundle();
        $bundle['status']='published'; $bundle['version']='test-v1'; $bundle['effectiveDate']='2026-10-31';
        $bundle['operator']=['name'=>'Test operator','address'=>'Test address','email'=>'test@example.com'];
        $bundle['terms']=[['title'=>'Terms','body'=>'Test agreement text.']];
        $bundle['privacy']=[['title'=>'Privacy','body'=>'Test notice text.']];
        $this->bundlePath=tempnam(sys_get_temp_dir(),'aadi-legal-'); file_put_contents($this->bundlePath,json_encode($bundle));
        config(['legal.enabled'=>true,'legal.bundle'=>$this->bundlePath]);
    }
    protected function tearDown(): void { if(isset($this->bundlePath)) @unlink($this->bundlePath); parent::tearDown(); }
    private function payload(string $context='account'): array {
        $legal=app(LegalDocuments::class); $doc=$legal->document($context);
        return ['context'=>$context,'version'=>$doc['version'],'hash'=>$legal->digest($doc),'terms'=>true,'privacy'=>true,'provider'=>true];
    }
    public function test_drafts_cannot_be_accepted_or_publicly_read(): void {
        $bundle=json_decode(file_get_contents($this->bundlePath),true); $bundle['status']='draft'; file_put_contents($this->bundlePath,json_encode($bundle));
        $this->get('/legal/terms')->assertNotFound();
        $this->actingAs(User::factory()->create())->post('/legal/accept',$this->payload())->assertNotFound();
        $this->assertDatabaseCount('legal_acceptances',0);
    }
    public function test_guests_can_read_published_terms_but_cannot_accept(): void {
        $this->get('/legal/terms')->assertOk();
        $this->post('/legal/accept',$this->payload())->assertRedirect('/login');
    }
    public function test_authenticated_acceptance_is_explicit_and_idempotent(): void {
        $user=User::factory()->create();$this->actingAs($user);
        $this->post('/legal/accept',array_replace($this->payload(),['terms'=>false]))->assertSessionHasErrors('terms');
        $this->post('/legal/accept',$this->payload())->assertRedirect('/dashboard');
        $record=DB::table('legal_acceptances')->first();
        $this->post('/legal/accept',$this->payload())->assertRedirect();
        $this->assertDatabaseCount('legal_acceptances',1);
        $this->assertSame($user->id,$record->user_id);
        $this->assertSame('Test agreement text.',json_decode($record->snapshot,true)['terms'][0]['body']);
        $this->assertSame($record->accepted_at,DB::table('legal_acceptances')->first()->accepted_at);
    }
    public function test_stale_documents_and_provider_without_account_agreement_are_rejected(): void {
        $this->actingAs(User::factory()->create());
        $this->post('/legal/accept',array_replace($this->payload(),['hash'=>str_repeat('0',64)]))->assertStatus(409);
        $this->post('/legal/accept',$this->payload('haul'))->assertStatus(409);
        $this->post('/legal/accept',$this->payload())->assertRedirect();
        $this->post('/legal/accept',array_replace($this->payload('haul'),['provider'=>false]))->assertSessionHasErrors('provider');
        $this->post('/legal/accept',$this->payload('haul'))->assertRedirect();
        $this->assertDatabaseCount('legal_acceptances',2);
    }
    public function test_gate_preserves_browsing_and_requires_account_then_role_acceptance(): void {
        $this->actingAs(User::factory()->create());
        $this->get('/provider-applications/haul')->assertRedirect('/legal/accept?context=account');
        $this->post('/legal/accept',$this->payload())->assertRedirect('/provider-applications/haul');
        $this->get('/provider-applications/haul')->assertRedirect('/legal/accept?context=haul');
        config(['legal.enabled'=>false]);
        $this->get('/provider-applications/haul')->assertOk();
    }
    public function test_changed_text_cannot_reuse_an_accepted_version(): void {
        $this->actingAs(User::factory()->create())->post('/legal/accept',$this->payload())->assertRedirect();
        $bundle=json_decode(file_get_contents($this->bundlePath),true);$bundle['terms'][0]['body']='Changed text';file_put_contents($this->bundlePath,json_encode($bundle));
        $this->actingAs(User::factory()->create())->post('/legal/accept',$this->payload())->assertStatus(409);
    }
}
