<?php

namespace Tests\Feature;

use App\Models\AccountReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AccountReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_require_authentication_and_only_admin_review_changes_scores(): void
    {
        $this->withoutVite();
        $reporter = User::factory()->create();
        $target = User::factory()->create();
        $target->trust_score = 20;
        $target->save();
        $admin = User::factory()->create(['is_admin' => true]);
        $payload = ['reason' => 'A detailed account of what occurred.'];
        $this->post("/users/{$target->id}/reports", $payload)->assertRedirect('/login');
        $this->actingAs($reporter)->post("/users/{$reporter->id}/reports", $payload)->assertSessionHasErrors('reason');
        $this->post("/users/{$target->id}/reports", $payload)->assertRedirect();
        $report = AccountReport::firstOrFail();
        $this->assertSame(20, $target->fresh()->trust_score);
        $decision = ['action' => 'confirm', 'points' => 7, 'note' => 'Evidence reviewed carefully.'];
        $this->get('/admin/reports')->assertForbidden();
        $this->patch("/admin/reports/{$report->id}", $decision)->assertForbidden();
        $this->actingAs($admin)->get('/admin/reports')->assertInertia(fn ($page) => $page->where('status', 'pending')->has('reports.data', 1));
        $this->patch("/admin/reports/{$report->id}", $decision)->assertRedirect();
        $this->assertSame(13, $target->fresh()->trust_score);
        $this->patch("/admin/reports/{$report->id}", $decision)->assertSessionHasErrors('action');
        $this->assertSame(13, $target->fresh()->trust_score);
        $this->patch("/admin/reports/{$report->id}", ['action' => 'reverse', 'note' => 'Decision was reconsidered.'])->assertRedirect();
        $this->assertSame(20, $target->fresh()->trust_score);
        $this->patch("/admin/reports/{$report->id}", ['action' => 'reverse', 'note' => 'Decision was reconsidered.'])->assertSessionHasErrors('action');
        $this->assertSame(2, DB::table('account_report_reviews')->count());
        $this->get('/admin/reports?status=reversed')->assertInertia(fn ($page) => $page
            ->where('reports.data.0.status', 'reversed')->has('reports.data.0.history', 2));
    }

    public function test_penalty_is_clamped_and_dismissal_has_no_effect(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $reporter = User::factory()->create();
        $target = User::factory()->create();
        $target->trust_score = 3;
        $target->save();
        $report = AccountReport::create(['reporter_id' => $reporter->id, 'reported_id' => $target->id, 'reason' => 'An account of the event.']);
        $this->actingAs($admin)->patch("/admin/reports/{$report->id}", ['action' => 'confirm', 'points' => 9, 'note' => 'Evidence was reviewed.'])->assertRedirect();
        $this->assertSame(0, $target->fresh()->trust_score);
        $this->assertSame(3, $report->fresh()->applied_points);
        $this->patch("/admin/reports/{$report->id}", ['action' => 'reverse', 'note' => 'Decision was reconsidered.'])->assertRedirect();
        $this->assertSame(3, $target->fresh()->trust_score);
        $other = AccountReport::create(['reporter_id' => $reporter->id, 'reported_id' => $target->id, 'reason' => 'Another account of events.']);
        $this->patch("/admin/reports/{$other->id}", ['action' => 'dismiss', 'note' => 'Insufficient evidence.'])->assertRedirect();
        $this->assertSame(3, $target->fresh()->trust_score);
    }

    public function test_versioned_api_can_submit_reports(): void
    {
        $reporter = User::factory()->create();
        $target = User::factory()->create();
        $url = "/api/v1/users/{$target->id}/reports";
        $this->postJson($url, ['reason' => 'A detailed account of what occurred.'])->assertUnauthorized();
        $this->actingAs($reporter, 'sanctum')->postJson($url, ['reason' => 'A detailed account of what occurred.'])
            ->assertCreated()->assertJsonPath('status', 'pending');
    }

    public function test_admin_report_list_supports_counts_search_and_pagination(): void
    {
        $this->withoutVite();
        $admin = User::factory()->create(['is_admin' => true]);
        $reporter = User::factory()->create(['name' => 'Taylor Reporter']);
        $target = User::factory()->create(['name' => 'Morgan Provider']);
        AccountReport::create(['reporter_id' => $reporter->id, 'reported_id' => $target->id, 'reason' => 'A detailed account of events.']);
        $dismissed = AccountReport::create(['reporter_id' => $reporter->id, 'reported_id' => $target->id, 'reason' => 'A second report.']);
        $dismissed->status = 'dismissed';
        $dismissed->save();

        $this->actingAs($admin)->get('/admin/reports?status=all&q=Morgan&per_page=10&sort=submitted&dir=asc')
            ->assertInertia(fn ($page) => $page->where('counts.pending', 1)->where('counts.dismissed', 1)
                ->where('counts.all', 2)->where('filters.q', 'Morgan')->where('sort', 'submitted')
                ->where('dir', 'asc')->where('reports.per_page', 10)->has('reports.data', 2));
        $this->get('/admin/reports?q=not-present')->assertInertia(fn ($page) => $page->where('counts.all', 2)->has('reports.data', 0));
    }

    public function test_admin_can_combine_report_filters_and_filter_zero_values(): void
    {
        $this->withoutVite();
        $admin = User::factory()->create(['is_admin' => true]);
        $otherAdmin = User::factory()->create(['is_admin' => true]);
        $reporter = User::factory()->create(['name' => 'Taylor Reporter', 'email' => 'reporter@example.test']);
        $target = User::factory()->create(['name' => 'Morgan Provider', 'email' => 'provider@example.test']);
        $target->trust_score = 10;
        $target->save();
        $report = AccountReport::create(['reporter_id' => $reporter->id, 'reported_id' => $target->id, 'reason' => 'A detailed account of events.']);
        $report->created_at = '2026-03-05 12:00:00';
        $report->status = 'confirmed';
        $report->applied_points = 0;
        $report->reviewed_by = $admin->id;
        $report->save();
        $other = AccountReport::create(['reporter_id' => $reporter->id, 'reported_id' => $target->id, 'reason' => 'Another account of events.']);
        $other->created_at = '2026-03-06 12:00:00';
        $other->status = 'confirmed';
        $other->applied_points = 5;
        $other->reviewed_by = $otherAdmin->id;
        $other->save();
        AccountReport::create(['reporter_id' => $reporter->id, 'reported_id' => $target->id, 'reason' => 'Pending report.']);

        $query = http_build_query(['status' => 'all', 'reporter' => 'reporter@example.test', 'reported' => 'Morgan',
            'from' => '2026-03-05', 'to' => '2026-03-05', 'score_min' => 10, 'score_max' => 10,
            'points_min' => 0, 'points_max' => 0, 'reviewer' => $admin->id, 'per_page' => 10]);
        $this->actingAs($admin)->get('/admin/reports?'.$query)->assertInertia(fn ($page) => $page
            ->where('reports.total', 1)->where('reports.data.0.id', $report->id)
            ->where('filters.points_max', '0')->where('filters.reviewer', $admin->id)->has('reviewers', 2)
            ->where('counts.all', 3));
        $this->get('/admin/reports?status=all&points_min=0&points_max=0')->assertInertia(fn ($page) => $page
            ->where('reports.total', 1)->where('reports.data.0.id', $report->id));
        $this->get('/admin/reports?status=all&points_min=5&points_max=5')->assertInertia(fn ($page) => $page
            ->where('reports.total', 1)->where('reports.data.0.id', $other->id));
    }

    public function test_admin_report_filters_reject_invalid_ranges(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->get('/admin/reports?from=2026-03-06&to=2026-03-05&score_min=12&score_max=1&points_min=5&points_max=1')
            ->assertSessionHasErrors(['to', 'score_max', 'points_max']);
        $this->get('/admin/reports?score_min=-1&points_max=256&from=invalid')
            ->assertSessionHasErrors(['score_min', 'points_max', 'from']);
    }
}
