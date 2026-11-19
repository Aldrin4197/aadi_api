<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AadiPlusEntitlementController extends Controller
{
    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'aadi_plus_until' => ['present', 'nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
        ]);

        $until = filled($data['aadi_plus_until'])
            ? Carbon::createFromFormat('Y-m-d', $data['aadi_plus_until'], config('app.timezone'))->endOfDay()
            : null;

        $user->forceFill(['aadi_plus_until' => $until])->save();

        return back()->with('success', $until
            ? 'Aadi Plus entitlement updated.'
            : 'Aadi Plus entitlement removed.');
    }
}
