<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountReport;
use App\Models\User;
use App\Support\AdminTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AccountReportController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'status' => ['nullable', Rule::in(['pending', 'confirmed', 'dismissed', 'reversed', 'all'])],
            'q' => ['nullable', 'string', 'max:150'],
            'reporter' => ['nullable', 'string', 'max:150'],
            'reported' => ['nullable', 'string', 'max:150'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', ...($request->filled('from') ? ['after_or_equal:from'] : [])],
            'score_min' => ['nullable', 'integer', 'min:0', 'max:255'],
            'score_max' => ['nullable', 'integer', 'min:0', 'max:255', ...($request->filled('score_min') ? ['gte:score_min'] : [])],
            'points_min' => ['nullable', 'integer', 'min:0', 'max:255'],
            'points_max' => ['nullable', 'integer', 'min:0', 'max:255', ...($request->filled('points_min') ? ['gte:points_min'] : [])],
            'reviewer' => ['nullable', 'string', 'size:26', Rule::exists('users', 'id')],
        ]);
        $status = $data['status'] ?? 'pending';
        $counts = AccountReport::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $query = AccountReport::with(['reporter:id,name', 'reported:id,name,trust_score', 'reviewer:id,name', 'reviews.admin:id,name'])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status));
        if (!empty($data['q'])) {
            $term = AdminTable::like($data['q']);
            $query->where(fn ($query) => $query->where('reason', 'like', $term)
                ->orWhereHas('reporter', fn ($user) => $user->where('name', 'like', $term))
                ->orWhereHas('reported', fn ($user) => $user->where('name', 'like', $term)));
        }
        foreach (['reporter', 'reported'] as $role) {
            if (!empty($data[$role])) {
                $term = AdminTable::like($data[$role]);
                $query->whereHas($role, fn ($user) => $user->where('name', 'like', $term)->orWhere('email', 'like', $term));
            }
        }
        if (!empty($data['from'])) $query->whereDate('created_at', '>=', $data['from']);
        if (!empty($data['to'])) $query->whereDate('created_at', '<=', $data['to']);
        foreach (['min' => '>=', 'max' => '<='] as $suffix => $operator) {
            if (isset($data["score_$suffix"])) {
                $score = (int) $data["score_$suffix"];
                $query->whereHas('reported', fn ($user) => $user->where('trust_score', $operator, $score));
            }
            if (isset($data["points_$suffix"])) {
                $query->where('applied_points', $operator, (int) $data["points_$suffix"]);
            }
        }
        if (isset($data['points_min']) || isset($data['points_max'])) $query->whereIn('status', ['confirmed', 'reversed']);
        if (!empty($data['reviewer'])) $query->where('reviewed_by', $data['reviewer']);
        $sort = AdminTable::sort($request, $query, [
            'submitted' => 'created_at',
            'status' => 'status',
        ], 'submitted');
        $reports = $query->paginate(AdminTable::perPage($request, 20, [10, 15, 20, 25, 50]))->withQueryString()->through(fn ($report) => [
                'id' => $report->id, 'reporter' => $report->reporter->name,
                'reported' => $report->reported->name, 'reportedId' => $report->reported_id,
                'trustScore' => $report->reported->trust_score, 'reason' => $report->reason,
                'status' => $report->status, 'points' => $report->applied_points,
                'reviewNote' => $report->review_note, 'reviewer' => $report->reviewer?->name,
                'createdAt' => $report->created_at->toDateTimeString(),
                'history' => $report->reviews->map(fn ($review) => [
                    'action' => $review->action, 'points' => $review->points, 'note' => $review->note,
                    'admin' => $review->admin?->name ?? 'Former administrator', 'createdAt' => $review->created_at,
                ])->all(),
            ]);
        return Inertia::render('Admin/Reports/Index', [
            'reports' => $reports, 'status' => $status,
            'filters' => collect(['q', 'reporter', 'reported', 'from', 'to', 'score_min', 'score_max', 'points_min', 'points_max', 'reviewer'])
                ->mapWithKeys(fn ($key) => [$key => isset($data[$key]) ? (string) $data[$key] : ''])->all(),
            'reviewers' => User::whereIn('id', AccountReport::whereNotNull('reviewed_by')->select('reviewed_by'))
                ->orderBy('name')->get(['id', 'name']),
            ...$sort,
            'counts' => [
                'pending' => (int) ($counts['pending'] ?? 0), 'confirmed' => (int) ($counts['confirmed'] ?? 0),
                'dismissed' => (int) ($counts['dismissed'] ?? 0), 'reversed' => (int) ($counts['reversed'] ?? 0),
                'all' => (int) $counts->sum(),
            ],
        ]);
    }

    public function update(Request $request, AccountReport $accountReport)
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['confirm', 'dismiss', 'reverse'])],
            'points' => ['required_if:action,confirm', 'nullable', 'integer', 'min:0', 'max:255'],
            'note' => ['required', 'string', 'min:10', 'max:2000'],
        ]);
        DB::transaction(function () use ($request, $accountReport, $data) {
            $report = AccountReport::whereKey($accountReport->id)->lockForUpdate()->firstOrFail();
            $user = User::whereKey($report->reported_id)->lockForUpdate()->firstOrFail();
            $action = $data['action'];
            if (($action === 'reverse' && $report->status !== 'confirmed') || ($action !== 'reverse' && $report->status !== 'pending')) {
                throw ValidationException::withMessages(['action' => 'This report has already been reviewed or reversed.']);
            }
            if ($action === 'confirm') {
                $report->applied_points = min($user->trust_score, (int) $data['points']);
                $user->trust_score -= $report->applied_points;
                $user->save();
            } elseif ($action === 'reverse') {
                $user->trust_score = min(255, $user->trust_score + $report->applied_points);
                $user->save();
            }
            $report->status = match ($action) { 'confirm' => 'confirmed', 'dismiss' => 'dismissed', 'reverse' => 'reversed' };
            $report->review_note = $data['note'];
            $report->reviewed_by = $request->user()->id;
            $report->reviewed_at = now();
            $report->save();
            DB::table('account_report_reviews')->insert([
                'account_report_id' => $report->id, 'admin_id' => $request->user()->id,
                'action' => $action, 'points' => $report->applied_points, 'note' => $data['note'], 'created_at' => now(),
            ]);
        });
        return back()->with('success', 'Report review saved.');
    }
}
