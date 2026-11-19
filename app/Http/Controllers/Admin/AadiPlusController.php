<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AadiPlusPerks;
use App\Support\AdminTable;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class AadiPlusController extends Controller
{
    public function index(Request $request, AadiPlusPerks $perks)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:active,expiring,expired'],
        ]);

        $members = User::query()->whereNotNull('aadi_plus_until')
            ->select(['id', 'name', 'email', 'avatar_path', 'avatar_url', 'aadi_plus_until'])
            ->when(! empty($filters['q']), fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', AdminTable::like($filters['q']))->orWhere('email', 'like', AdminTable::like($filters['q']))))
            ->when(($filters['status'] ?? null) === 'active', fn ($q) => $q->where('aadi_plus_until', '>', now()))
            ->when(($filters['status'] ?? null) === 'expiring', fn ($q) => $q->whereBetween('aadi_plus_until', [now(), now()->addDays(30)]))
            ->when(($filters['status'] ?? null) === 'expired', fn ($q) => $q->where('aadi_plus_until', '<=', now()))
            ->orderByDesc('aadi_plus_until')
            ->paginate(15)->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'avatar_url' => $user->avatar_url,
                'until' => $user->aadi_plus_until->toIso8601String(), 'untilDate' => $user->aadi_plus_until->format('Y-m-d'), 'active' => $user->hasAadiPlus(),
            ]);

        return Inertia::render('Admin/AadiPlus/Index', [
            'members' => $members,
            'filters' => $filters,
            'summary' => [
                'active' => User::where('aadi_plus_until', '>', now())->count(),
                'expiring' => User::whereBetween('aadi_plus_until', [now(), now()->addDays(30)])->count(),
                'expired' => User::where('aadi_plus_until', '<=', now())->count(),
            ],
            'perks' => array_values($perks->all()),
            'plusEnabled' => $perks->enabled(),
        ]);
    }

    public function updatePerk(Request $request, AadiPlusPerks $perks, string $perk)
    {
        $definition = AadiPlusPerks::DEFINITIONS[$perk] ?? abort(404);
        $max = $definition['max'] ?? 100000;
        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
            'freeLimit' => ['nullable', 'integer', 'min:0', 'max:'.$max],
            'plusLimit' => ['nullable', 'integer', 'min:0', 'max:'.$max],
        ]);

        $perks->update($perk, $data['enabled'], $data['freeLimit'] ?? null, $data['plusLimit'] ?? null);

        return back()->with('success', $definition['label'].' updated.');
    }

    public function grant(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'until' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
        ]);

        User::where('email', $data['email'])->firstOrFail()->forceFill([
            'aadi_plus_until' => Carbon::createFromFormat('Y-m-d', $data['until'], config('app.timezone'))->endOfDay(),
        ])->save();

        return back()->with('success', 'Aadi Plus granted to '.$data['email'].'.');
    }
}
