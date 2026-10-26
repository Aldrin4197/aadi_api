<?php
namespace App\Http\Controllers\Marketplace;
use App\Http\Controllers\Controller;
use App\Models\{ProviderApplication, User};
use App\Services\Marketplace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Storage};
use Illuminate\Validation\{Rule, ValidationException};
use Inertia\Inertia;

class ProviderApplicationController extends Controller {
    public function __construct(private Marketplace $market) {}
    public function index(Request $request) {
        $statuses = [];
        foreach (array_keys(Marketplace::TYPES) as $service) {
            $statuses[$service] = $request->user()->{$this->market->statusField($service)}?->value ?? 'none';
        }
        return $request->is('api/*') ? response()->json(['data'=>['statuses'=>$statuses]]) : Inertia::render('Marketplace/ProviderStart', ['statuses' => $statuses]);
    }
    public function show(Request $request, string $service) {
        $field = $this->market->statusField($service);
        $application = ProviderApplication::where('user_id', $request->user()->id)->where('service', $service)->first();
        $data = [
            'service' => $service,
            'status' => $request->user()->$field?->value ?? 'none',
            'application' => $application?->toReviewData($request->is('api/*')),
        ];
        return $request->is('api/*') ? response()->json(['data'=>$data]) : Inertia::render('Marketplace/ProviderApply', $data);
    }
    public function store(Request $request, string $service) {
        $field = $this->market->statusField($service);
        $data = $request->validate([
            'displayName' => 'required|string|max:255', 'phone' => 'required|string|min:7|max:40',
            'area' => 'required|string|max:255', 'description' => 'required|string|min:20|max:3000',
            'documents' => 'required|array|min:1|max:5',
            'documents.*' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:5120', 'confirmed' => 'accepted',
        ]);
        $stored = []; $previous = [];
        try {
            DB::transaction(function () use ($request, $service, $field, $data, &$stored, &$previous) {
                $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
                $existing = ProviderApplication::where('user_id', $user->id)->where('service', $service)->first();
                if ($user->$field?->value === 'approved' || ($user->$field?->value === 'pending' && $existing)) {
                    throw ValidationException::withMessages(['application' => 'This role is already approved or under review.']);
                }
                foreach ($request->file('documents') as $file) {
                    $path = $file->store('provider-documents/'.$user->id, 'local');
                    if (!$path) throw new \RuntimeException('Unable to store verification document.');
                    $stored[] = ['path' => $path, 'name' => basename($file->getClientOriginalName())];
                }
                $previous = $existing?->documents ?? [];
                ProviderApplication::updateOrCreate(['user_id' => $user->id, 'service' => $service], [
                    'status' => 'pending', 'display_name' => $data['displayName'], 'phone' => $data['phone'],
                    'area' => $data['area'], 'description' => $data['description'], 'documents' => $stored,
                    'review_note' => null, 'reviewer_id' => null, 'decided_at' => null, 'submitted_at' => now(),
                ]);
                $user->update([$field => 'pending']);
            });
        } catch (\Throwable $error) {
            foreach ($stored as $document) Storage::disk('local')->delete($document['path']);
            throw $error;
        }
        foreach ($previous as $document) Storage::disk('local')->delete($document['path']);
        if ($request->is('api/*')) return response()->json(['data'=>ProviderApplication::where('user_id', $request->user()->id)->where('service', $service)->firstOrFail()->toReviewData(true)], 201);
        return redirect('/provider-applications/'.$service)->with('success', 'Application submitted for review. You can keep browsing with your User account.');
    }
    public function document(Request $request, string $id, string $index) {
        $application = ProviderApplication::findOrFail($id);
        abort_unless($request->user()->is_admin || (string) $application->user_id === (string) $request->user()->id, 403);
        abort_unless(ctype_digit($index), 404);
        $document = $application->documents[(int) $index] ?? null;
        abort_unless($document && Storage::disk('local')->exists($document['path']), 404);
        return Storage::disk('local')->download($document['path'], $document['name'], ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
    public function queue() {
        $users = User::where(fn($q) => $q->where('landlord_status','pending')->orWhere('vehicle_owner_status','pending')->orWhere('service_provider_status','pending'))->get();
        $applications = ProviderApplication::whereIn('user_id', $users->pluck('id'))->get()->keyBy(fn($a) => $a->user_id.':'.$a->service);
        $rows = [];
        foreach ($users as $user) foreach (array_keys(Marketplace::TYPES) as $service) {
            if ($user->{$this->market->statusField($service)}?->value !== 'pending') continue;
            $rows[] = ['userId'=>$user->id, 'name'=>$user->name, 'email'=>$user->email, 'service'=>$service,
                'application'=>($applications[$user->id.':'.$service] ?? null)?->toReviewData()];
        }
        return Inertia::render('Marketplace/Approvals', ['applications'=>$rows]);
    }
    public function decide(Request $request, string $id, string $service) {
        $field = $this->market->statusField($service);
        $data = $request->validate(['status'=>['required',Rule::in(['approved','rejected'])], 'reviewNote'=>'required_if:status,rejected|nullable|string|max:2000']);
        DB::transaction(function () use ($request, $id, $service, $field, $data) {
            $user = User::whereKey($id)->lockForUpdate()->firstOrFail();
            $application = ProviderApplication::where('user_id',$id)->where('service',$service)->first();
            abort_unless($user->$field?->value === 'pending',422,'No pending application.');
            if (!$application && $data['status'] === 'approved') {
                throw ValidationException::withMessages(['application'=>'This older request has no supporting documents. Ask the user to complete their application.']);
            }
            $user->update([$field=>$data['status']]);
            $application?->update(['status'=>$data['status'], 'review_note'=>$data['reviewNote'] ?? null, 'reviewer_id'=>$request->user()->id, 'decided_at'=>now()]);
        });
        return back()->with('success','Application reviewed.');
    }
}
