<?php

namespace App\Services;

use App\Models\SupportTicket;
use Carbon\CarbonInterface;

/** Support desk analytics: volume, backlog, response and resolution times, with plain-language insights. */
class SupportAnalytics
{
    public const RESPONSE_TARGET_HOURS = 24;

    public function build(int $days): array
    {
        $days = in_array($days, AdminOverview::RANGES, true) ? $days : 30;
        $since = today()->subDays($days - 1);
        $previousSince = $since->copy()->subDays($days);
        $dates = collect(range(0, $days - 1))->map(fn ($i) => $since->copy()->addDays($i)->format('Y-m-d'))->all();

        $created = SupportTicket::where('created_at', '>=', $previousSince)->get(['id', 'category', 'priority', 'status', 'created_at', 'first_response_at', 'resolved_at']);
        $resolved = SupportTicket::whereNotNull('resolved_at')->where('resolved_at', '>=', $previousSince)->get(['id', 'created_at', 'resolved_at']);

        $inWindow = fn ($row, $column) => $row->{$column} && $row->{$column} >= $since;
        $inPrevious = fn ($row, $column) => $row->{$column} && $row->{$column} >= $previousSince && $row->{$column} < $since;
        $current = $created->filter(fn ($t) => $inWindow($t, 'created_at'));
        $previous = $created->filter(fn ($t) => $inPrevious($t, 'created_at'));
        $resolvedNow = $resolved->filter(fn ($t) => $inWindow($t, 'resolved_at'));
        $resolvedBefore = $resolved->filter(fn ($t) => $inPrevious($t, 'resolved_at'));

        $hours = fn ($rows, $from, $to) => $rows->filter(fn ($t) => $t->{$from} && $t->{$to})->map(fn ($t) => max(0, $t->{$from}->diffInMinutes($t->{$to}, true)) / 60);
        $average = fn ($values) => $values->count() ? round($values->avg(), 1) : null;
        $firstResponse = $hours($current, 'created_at', 'first_response_at');
        $firstResponsePrevious = $hours($previous, 'created_at', 'first_response_at');
        $resolutionTime = $hours($resolvedNow, 'created_at', 'resolved_at');
        $resolutionPrevious = $hours($resolvedBefore, 'created_at', 'resolved_at');

        $dueForResponse = $current->filter(fn ($t) => $t->first_response_at || $t->created_at->diffInHours(now(), true) >= self::RESPONSE_TARGET_HOURS);
        $metTarget = $dueForResponse->filter(fn ($t) => $t->first_response_at && $t->created_at->diffInHours($t->first_response_at, true) <= self::RESPONSE_TARGET_HOURS);
        $targetRate = $dueForResponse->count() ? (int) round($metTarget->count() / $dueForResponse->count() * 100) : null;

        $status = SupportTicket::selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');
        $active = SupportTicket::whereIn('status', SupportTicket::ACTIVE);
        $backlog = (clone $active)->get(['created_at', 'priority', 'first_response_at']);
        $unanswered = $backlog->whereNull('first_response_at');
        $oldestUnanswered = $unanswered->min('created_at');
        $urgent = $backlog->whereIn('priority', ['high', 'urgent'])->count();

        $ageBuckets = [['label' => 'Under 1 day', 'min' => 0, 'max' => 1], ['label' => '1–3 days', 'min' => 1, 'max' => 3], ['label' => '3–7 days', 'min' => 3, 'max' => 7], ['label' => 'Over 7 days', 'min' => 7, 'max' => null]];
        $ages = array_map(fn ($b) => ['label' => $b['label'], 'count' => $backlog->filter(function ($t) use ($b) {
            $age = $t->created_at->diffInHours(now(), true) / 24;

            return $age >= $b['min'] && ($b['max'] === null || $age < $b['max']);
        })->count()], $ageBuckets);

        $categories = collect(SupportTicket::CATEGORIES)->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'count' => $current->where('category', $key)->count()])->values();
        $dailyCreated = $current->groupBy(fn ($t) => $t->created_at->format('Y-m-d'))->map->count();
        $dailyResolved = $resolvedNow->groupBy(fn ($t) => $t->resolved_at->format('Y-m-d'))->map->count();

        $data = [
            'range' => ['days' => $days, 'options' => AdminOverview::RANGES],
            'totals' => ['all' => $status->sum(), 'active' => $active->count(), 'open' => (int) ($status['open'] ?? 0), 'in_progress' => (int) ($status['in_progress'] ?? 0), 'resolved' => (int) ($status['resolved'] ?? 0), 'closed' => (int) ($status['closed'] ?? 0)],
            'kpis' => [
                ['key' => 'tickets', 'label' => 'New tickets', 'value' => $current->count(), 'delta' => $this->delta($current->count(), $previous->count()), 'series' => array_map(fn ($d) => (int) ($dailyCreated[$d] ?? 0), $dates), 'icon' => 'tickets', 'tone' => 'neutral', 'hint' => "{$previous->count()} in the previous {$days} days", 'scope' => "Last {$days} days"],
                ['key' => 'backlog', 'label' => 'Open backlog', 'value' => $active->count(), 'delta' => null, 'series' => [], 'icon' => 'backlog', 'tone' => $active->count() ? 'warning' : 'good', 'hint' => $unanswered->count() ? "{$unanswered->count()} still unanswered" : 'Everything has a reply', 'scope' => 'Right now'],
                ['key' => 'response', 'label' => 'First response', 'value' => $average($firstResponse), 'suffix' => ' h', 'delta' => $this->delta($average($firstResponse), $average($firstResponsePrevious), true), 'series' => [], 'icon' => 'response', 'tone' => ($average($firstResponse) ?? 0) > self::RESPONSE_TARGET_HOURS ? 'warning' : 'good', 'hint' => $targetRate === null ? 'No replies to measure yet' : "{$targetRate}% within ".self::RESPONSE_TARGET_HOURS.'h', 'scope' => 'Average hours', 'invert' => true],
                ['key' => 'resolution', 'label' => 'Time to resolve', 'value' => $average($resolutionTime), 'suffix' => ' h', 'delta' => $this->delta($average($resolutionTime), $average($resolutionPrevious), true), 'series' => array_map(fn ($d) => (int) ($dailyResolved[$d] ?? 0), $dates), 'icon' => 'resolution', 'tone' => 'neutral', 'hint' => "{$resolvedNow->count()} resolved in this period", 'scope' => 'Average hours', 'invert' => true],
            ],
            'trend' => ['dates' => $dates, 'created' => array_map(fn ($d) => (int) ($dailyCreated[$d] ?? 0), $dates), 'resolved' => array_map(fn ($d) => (int) ($dailyResolved[$d] ?? 0), $dates)],
            'categories' => $categories,
            'ages' => $ages,
            'insights' => [],
        ];
        $data['insights'] = $this->insights($data, $days, $unanswered->count(), $oldestUnanswered, $urgent, $targetRate, $current->count(), $previous->count(), $resolvedNow->count());

        return $data;
    }

    private function delta(int|float|null $current, int|float|null $previous, bool $allowZero = false): ?int
    {
        if ($current === null || $previous === null || ($previous == 0 && !$allowZero)) return null;

        return $previous == 0 ? null : (int) round(($current - $previous) / $previous * 100);
    }

    private function insights(array $data, int $days, int $unanswered, ?CarbonInterface $oldest, int $urgent, ?int $targetRate, int $created, int $previous, int $resolved): array
    {
        $items = [];
        if ($unanswered > 0) {
            $wait = $oldest ? ' The oldest has waited '.$this->age($oldest).'.' : '';
            $items[] = ['tone' => 'warning', 'title' => "{$unanswered} ".($unanswered === 1 ? 'ticket has' : 'tickets have').' no reply yet', 'text' => 'Replying early is the biggest driver of customer trust.'.$wait];
        } elseif ($data['totals']['all'] > 0) {
            $items[] = ['tone' => 'good', 'title' => 'Every active ticket has a reply', 'text' => 'Nothing is waiting on a first response.'];
        }
        if ($urgent > 0) {
            $items[] = ['tone' => 'warning', 'title' => "{$urgent} high-priority ".($urgent === 1 ? 'ticket is' : 'tickets are').' still open', 'text' => 'Reports about listings or users are marked high priority automatically.'];
        }
        if ($targetRate !== null) {
            $items[] = ['tone' => $targetRate >= 80 ? 'good' : ($targetRate >= 50 ? 'info' : 'warning'), 'title' => "{$targetRate}% answered within ".self::RESPONSE_TARGET_HOURS.' hours', 'text' => 'Share of this period\'s tickets that met the first-response target.'];
        }
        if ($created > 0 || $previous > 0) {
            $delta = $this->delta($created, $previous);
            $items[] = ['tone' => 'info', 'title' => $delta === null ? "{$created} new ".($created === 1 ? 'ticket' : 'tickets') : 'Volume is '.($delta >= 0 ? 'up ' : 'down ').abs($delta).'%', 'text' => "{$created} opened and {$resolved} resolved in the last {$days} days; {$previous} opened in the period before."];
        }
        $top = collect($data['categories'])->sortByDesc('count')->first();
        if ($top && $top['count'] > 0 && $created >= 3) {
            $share = (int) round($top['count'] / $created * 100);
            $items[] = ['tone' => 'info', 'title' => "Most tickets are about “{$top['label']}”", 'text' => "{$share}% of this period's tickets. Consider a help article or a product fix for it."];
        }
        if (!$items) {
            $items[] = ['tone' => 'info', 'title' => 'No tickets yet', 'text' => 'Messages sent from the Support form on the home page will appear here.'];
        }

        return $items;
    }

    private function age(CarbonInterface $from): string
    {
        $hours = (int) $from->diffInHours(now(), true);

        return $hours < 48 ? max(1, $hours).' '.($hours === 1 ? 'hour' : 'hours') : intdiv($hours, 24).' days';
    }
}
