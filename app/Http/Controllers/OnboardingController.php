<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OnboardingController extends Controller
{
    public function update(Request $request) {
        $request->validate(['action'=>['required',Rule::in(['save','skip'])]]);
        if ($request->input('action') === 'skip') {
            if ($request->user()->onboarding_status !== 'completed') {
                $request->user()->forceFill(['onboarding_status'=>'skipped'])->save();
            }
            return back()->with('success', 'You can finish getting started from your profile. Unsaved answers were not stored.');
        }
        $data = $request->validate([
            'action'=>['required',Rule::in(['save','skip'])],
            'personas'=>'present|array|max:5',
            'personas.*'=>['string','distinct',Rule::in(['student','employee','business_owner','freelancer','other'])],
            'interests'=>'present|array|max:3',
            'interests.*'=>['string','distinct',Rule::in(['spaces','haul','raketeer'])],
            'city'=>'nullable|string|max:120',
            'address'=>'nullable|string|max:255',
            'budget_min'=>'nullable|numeric|min:0|max:99999999',
            'budget_max'=>array_filter(['nullable','numeric','min:0','max:99999999',$request->filled('budget_min') ? 'gte:budget_min' : null]),
            'budget_unit'=>['required',Rule::in(['month','day','hour','project'])],
        ]);
        $action = $data['action']; unset($data['action']);
        $request->user()->forceFill([
            'onboarding_data'=>$data,
            'onboarding_status'=>$action === 'skip' ? 'skipped' : 'completed',
        ])->save();
        return back()->with('success', $action === 'skip' ? 'You can finish getting started from your profile.' : 'Your preferences have been saved.');
    }
}
