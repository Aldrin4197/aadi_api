<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\TicketReply;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use App\Services\AdminOverview;
use App\Services\Notifier;
use App\Services\SupportAnalytics;
use App\Support\AdminTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class TicketController extends Controller
{
    public function index(Request $request, SupportAnalytics $analytics)
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(['active', 'all', ...array_keys(SupportTicket::STATUSES)])],
            'category' => ['nullable', Rule::in(array_keys(SupportTicket::CATEGORIES))],
            'priority' => ['nullable', Rule::in(array_keys(SupportTicket::PRIORITIES))],
            'assigned' => ['nullable', 'string', 'max:26', fn ($attribute, $value, $fail) => $this->validAssignee($value, ['me', 'unassigned']) ?: $fail('Choose an administrator.')],
            'q' => 'nullable|string|max:150',
            'range' => ['nullable', Rule::in(array_map('strval', AdminOverview::RANGES))],
            'view' => ['nullable', Rule::in(['tickets', 'insights'])],
        ]);
        $status = $filters['status'] ?? 'active';
        $view = $filters['view'] ?? 'tickets';
        $counts = SupportTicket::selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');
        $shared = [
            'view' => $view,
            'counts' => ['active' => $counts->only(SupportTicket::ACTIVE)->sum(), 'all' => $counts->sum(), ...array_map(fn ($key) => (int) ($counts[$key] ?? 0), array_combine(array_keys(SupportTicket::STATUSES), array_keys(SupportTicket::STATUSES)))],
            'options' => ['categories' => SupportTicket::CATEGORIES, 'statuses' => SupportTicket::STATUSES, 'priorities' => SupportTicket::PRIORITIES, 'staff' => $this->staff()],
        ];

        if ($view === 'insights') {
            return Inertia::render('Admin/Tickets/Index', [
                ...$shared,
                'filters' => ['status' => 'active', 'category' => '', 'priority' => '', 'assigned' => '', 'q' => '', 'range' => (string) ($filters['range'] ?? 30)],
                'tickets' => null,
                'analytics' => $analytics->build((int) ($filters['range'] ?? 30)),
            ]);
        }

        $query = SupportTicket::query()->with('assignee:id,name')->withCount(['messages as replies_count' => fn ($q) => $q->where('kind', 'reply')]);
        if ($status === 'active') $query->whereIn('status', SupportTicket::ACTIVE);
        elseif ($status !== 'all') $query->where('status', $status);
        if (!empty($filters['category'])) $query->where('category', $filters['category']);
        if (!empty($filters['priority'])) $query->where('priority', $filters['priority']);
        $assigned = $filters['assigned'] ?? null;
        if ($assigned === 'me') $query->where('assigned_to', $request->user()->id);
        elseif ($assigned === 'unassigned') $query->whereNull('assigned_to');
        elseif ($assigned) $query->where('assigned_to', $assigned);
        if (!empty($filters['q'])) {
            $like = AdminTable::like($filters['q']);
            $query->where(fn ($q) => $q->where('reference', 'like', $like)->orWhere('subject', 'like', $like)->orWhere('name', 'like', $like)->orWhere('email', 'like', $like)->orWhere('message', 'like', $like));
        }

        $sort = AdminTable::sort($request, $query, [
            'created' => 'created_at',
            'activity' => 'last_activity_at',
            'subject' => 'subject',
            'requester' => 'name',
            'category' => 'category',
            'priority' => fn ($q, $dir) => $q->orderByRaw("CASE priority WHEN 'urgent' THEN 4 WHEN 'high' THEN 3 WHEN 'normal' THEN 2 ELSE 1 END $dir"),
            'status' => fn ($q, $dir) => $q->orderByRaw("CASE status WHEN 'open' THEN 1 WHEN 'in_progress' THEN 2 WHEN 'resolved' THEN 3 ELSE 4 END $dir"),
            'assignee' => fn ($q, $dir) => $q->orderByRaw("(select name from users where users.id = support_tickets.assigned_to) $dir"),
        ], 'created');

        $tickets = $query->paginate(AdminTable::perPage($request))->withQueryString()->through(fn ($ticket) => $this->row($ticket));

        return Inertia::render('Admin/Tickets/Index', [
            ...$shared,
            'tickets' => $tickets,
            'filters' => ['status' => $status, 'category' => $filters['category'] ?? '', 'priority' => $filters['priority'] ?? '', 'assigned' => $filters['assigned'] ?? '', 'q' => $filters['q'] ?? '', 'range' => (string) ($filters['range'] ?? 30)],
            ...$sort,
            'analytics' => null,
        ]);
    }

    /** Administrators a ticket can be handed to; they are the only people who can open the desk. */
    private function staff(): array
    {
        return User::where('is_admin', true)->orderBy('name')->get(['id', 'name'])->map(fn ($admin) => ['id' => (string) $admin->id, 'name' => $admin->name])->all();
    }

    private function validAssignee(?string $value, array $keywords): bool
    {
        return $value === null || $value === '' || in_array($value, $keywords, true) || User::whereKey($value)->where('is_admin', true)->exists();
    }

    public function show(SupportTicket $ticket)
    {
        $ticket->load(['assignee:id,name', 'user:id,name', 'messages.author:id,name']);

        return response()->json(['ticket' => [
            ...$this->row($ticket),
            'message' => $ticket->message,
            'userId' => $ticket->user_id,
            'messages' => $ticket->messages->map(fn ($m) => [
                'id' => $m->id, 'kind' => $m->kind, 'body' => $m->body, 'emailed' => $m->emailed,
                'author' => $m->author?->name ?? 'System', 'createdAt' => $m->created_at->toIso8601String(),
            ])->values(),
        ]])->header('Cache-Control', 'private, no-store');
    }

    public function update(Request $request, SupportTicket $ticket)
    {
        $data = $request->validate([
            'status' => ['sometimes', Rule::in(array_keys(SupportTicket::STATUSES))],
            'priority' => ['sometimes', Rule::in(array_keys(SupportTicket::PRIORITIES))],
            'assignee' => ['sometimes', 'string', 'max:26', fn ($attribute, $value, $fail) => $this->validAssignee($value, ['me', 'none']) ?: $fail('Choose an administrator.')],
        ]);
        $events = [];
        $newAssignee = null;
        $admin = $request->user();

        if (isset($data['status']) && $data['status'] !== $ticket->status) {
            $ticket->status = $data['status'];
            $ticket->resolved_at = in_array($data['status'], ['resolved', 'closed'], true) ? ($ticket->resolved_at ?? now()) : null;
            $events[] = 'Status changed to '.SupportTicket::STATUSES[$data['status']].'.';
        }
        if (isset($data['priority']) && $data['priority'] !== $ticket->priority) {
            $ticket->priority = $data['priority'];
            $events[] = 'Priority changed to '.SupportTicket::PRIORITIES[$data['priority']].'.';
        }
        if (isset($data['assignee'])) {
            $assignedTo = match ($data['assignee']) { 'me' => $admin->id, 'none' => null, default => $data['assignee'] };
            if ((string) $assignedTo !== (string) $ticket->assigned_to) {
                $assignee = $assignedTo ? ($assignedTo === $admin->id ? $admin : User::find($assignedTo)) : null;
                $ticket->assigned_to = $assignedTo;
                $events[] = $assignee ? 'Assigned to '.$assignee->name.($assignee->id === $admin->id ? '.' : ' by '.$admin->name.'.') : 'Unassigned.';
                $newAssignee = $assignee;
            }
        }
        if ($events) {
            $ticket->last_activity_at = now();
            $ticket->save();
            foreach ($events as $event) $ticket->messages()->create(['user_id' => $admin->id, 'kind' => 'event', 'body' => $event]);
            if (isset($data['status']) && $data['status'] === 'resolved' && $ticket->wasChanged('status')) app(Notifier::class)->ticketResolved($ticket);
            if ($newAssignee && $newAssignee->id !== $admin->id) app(Notifier::class)->ticketAssigned($ticket, $newAssignee, $admin);
        }

        return $this->show($ticket->fresh());
    }

    public function message(Request $request, SupportTicket $ticket)
    {
        $data = $request->validate(['kind' => ['required', Rule::in(['reply', 'note'])], 'body' => 'required|string|min:2|max:4000']);
        $message = $ticket->messages()->create(['user_id' => $request->user()->id, 'kind' => $data['kind'], 'body' => $data['body']]);

        if ($data['kind'] === 'reply') {
            try {
                Mail::to($ticket->email, $ticket->name)->send(new TicketReply($ticket, $data['body']));
                $message->update(['emailed' => true]);
            } catch (\Throwable $error) {
                report($error);
            }
            $ticket->first_response_at ??= now();
            if ($ticket->status === 'open') $ticket->status = 'in_progress';
            app(Notifier::class)->ticketReplied($ticket);
        }
        $ticket->last_activity_at = now();
        $ticket->save();

        return $this->show($ticket->fresh());
    }

    private function row(SupportTicket $ticket): array
    {
        return [
            'id' => $ticket->id, 'reference' => $ticket->reference, 'subject' => $ticket->subject, 'name' => $ticket->name, 'email' => $ticket->email,
            'category' => $ticket->category, 'priority' => $ticket->priority, 'status' => $ticket->status, 'member' => $ticket->user_id !== null,
            'assignee' => $ticket->assignee?->name, 'assignedToId' => $ticket->assigned_to,
            'preview' => \Illuminate\Support\Str::limit($ticket->message, 110),
            'repliesCount' => $ticket->replies_count ?? null,
            'createdAt' => $ticket->created_at->toIso8601String(), 'lastActivityAt' => $ticket->last_activity_at?->toIso8601String(),
            'firstResponseAt' => $ticket->first_response_at?->toIso8601String(), 'resolvedAt' => $ticket->resolved_at?->toIso8601String(),
        ];
    }
}
