<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use App\Models\MarketplaceRequest;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate(['month' => ['nullable', 'date_format:Y-m'], 'date' => ['nullable', 'date_format:Y-m-d']]);
        $start = isset($data['month'])
            ? CarbonImmutable::createFromFormat('!Y-m', $data['month'], config('app.timezone'))
            : CarbonImmutable::now(config('app.timezone'))->startOfMonth();
        $end = $start->addMonth();
        $user = $request->user();
        $personal = CalendarEvent::where('user_id', $user->id)
            ->where('event_date', '>=', $start->toDateString())->where('event_date', '<', $end->toDateString())
            ->orderBy('event_date')->orderBy('event_time')->get()
            ->map(fn (CalendarEvent $event) => [
                'id' => $event->id, 'kind' => 'personal', 'title' => $event->title,
                'date' => $event->event_date->format('Y-m-d'), 'time' => $event->event_time, 'note' => $event->note,
            ]);
        $requests = MarketplaceRequest::where('status', 'accepted')
            ->where(fn ($query) => $query->where('customer_id', $user->id)->orWhere('provider_id', $user->id))
            ->where('requested_at', '>=', $start->toDateTimeString())->where('requested_at', '<', $end->toDateTimeString())
            ->orderBy('requested_at')->get()
            ->map(fn (MarketplaceRequest $item) => [
                'id' => $item->id, 'kind' => 'request', 'title' => $item->listing_title,
                'date' => $item->requested_at->format('Y-m-d'),
                'time' => $item->service === 'spaces' ? $item->requested_at->format('H:i') : null,
                'service' => $item->service, 'isProvider' => (string) $item->provider_id === (string) $user->id,
            ]);

        return Inertia::render('Account/Calendar', [
            'month' => $start->format('Y-m'), 'today' => now(config('app.timezone'))->toDateString(),
            'selectedDate' => isset($data['date']) && str_starts_with($data['date'], $start->format('Y-m').'-') ? $data['date'] : null,
            'timezone' => config('app.timezone'), 'events' => $personal->concat($requests)->values(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $event = new CalendarEvent($data);
        $event->user_id = $request->user()->id;
        $event->save();
        return redirect('/calendar?'.http_build_query(['month' => substr($data['event_date'], 0, 7), 'date' => $data['event_date']]))->with('success', 'Personal event added.');
    }

    public function update(Request $request, CalendarEvent $calendarEvent)
    {
        abort_unless((string) $calendarEvent->user_id === (string) $request->user()->id, 403);
        $data = $this->validated($request);
        $calendarEvent->update($data);
        return redirect('/calendar?'.http_build_query(['month' => substr($data['event_date'], 0, 7), 'date' => $data['event_date']]))->with('success', 'Personal event updated.');
    }

    public function destroy(Request $request, CalendarEvent $calendarEvent)
    {
        abort_unless((string) $calendarEvent->user_id === (string) $request->user()->id, 403);
        $calendarEvent->delete();
        return back()->with('success', 'Personal event deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'event_date' => ['required', 'date_format:Y-m-d'],
            'event_time' => ['nullable', 'date_format:H:i'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
