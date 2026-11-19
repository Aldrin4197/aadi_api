<?php

namespace App\Http\Controllers;

use App\Models\AccountReport;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AccountReportController extends Controller
{
    public function store(Request $request, User $user)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:20', 'max:4000']]);
        if ($request->user()->is($user)) {
            throw ValidationException::withMessages(['reason' => 'You cannot report your own account.']);
        }
        $report = AccountReport::create([
            'reporter_id' => $request->user()->id,
            'reported_id' => $user->id,
            'reason' => $data['reason'],
        ]);
        if ($request->is('api/*')) return response()->json(['id' => $report->id, 'status' => 'pending'], 201);
        return back()->with('success', 'Your report was submitted for administrator review.');
    }
}
