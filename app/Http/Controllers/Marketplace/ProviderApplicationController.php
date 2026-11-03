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
        $applications = ProviderApplication::where('user_id', $request->user()->id)->get()->mapWithKeys(fn ($application) => [$application->service => ['status'=>$application->status, 'reviewNote'=>$application->review_note]]);
        return Inertia::render('Marketplace/ProviderStart', ['statuses' => $statuses, 'applications' => (object) $applications->all()]);
    }
    public function show(Request $request, string $service) {
        $field = $this->market->statusField($service);
        $application = ProviderApplication::where('user_id', $request->user()->id)->where('service', $service)->first();
        $data = [
            'requirements' => config('provider_verification.'.$service, []),
            'service' => $service,
            'status' => $request->user()->$field?->value ?? 'none',
            'application' => $application?->toReviewData($request->is('api/*')),
        ];
        return $request->is('api/*') ? response()->json(['data'=>$data])->header('Cache-Control','private, no-store') : Inertia::render('Marketplace/ProviderApply', $data);
    }
    public function store(Request $request, string $service) {
        $field = $this->market->statusField($service);
        $schema = config('provider_verification.'.$service, []);
        $draft = $request->input('action') === 'draft';
        $data = $request->validate([
            'version' => 'nullable|string|size:64',
            'action' => ['sometimes', Rule::in(['draft','submit'])],
            'displayName' => 'required|string|max:255', 'phone' => ($draft ? 'nullable' : 'required').'|string|min:7|max:40',
            'area' => ($draft ? 'nullable' : 'required').'|string|max:255',
            'description' => ($draft ? 'nullable' : 'required').'|string|max:3000'.($draft ? '' : '|min:20'),
            'details.applicantType' => ['required',Rule::in(['individual','business'])],
            'details.businessName' => 'nullable|required_if:details.applicantType,business|string|max:255',
            'details.relationship' => 'nullable|string|max:255',
            'details.serviceCategory' => 'nullable|string|max:255',
            'details.skills' => 'nullable|string|max:1000',
            'details.experienceYears' => 'nullable|integer|min:0|max:80',
            'documents' => 'sometimes|array|list|max:10',
            'documents.*' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            'documentMeta' => 'sometimes|array|list|max:10',
            'documentMeta.*.type' => ['required',Rule::in(array_keys($schema))],
            'documentMeta.*.reference' => 'nullable|string|max:255',
            'documentMeta.*.expiresAt' => 'nullable|date_format:Y-m-d',
            'keepDocuments' => 'sometimes|array|list|max:10', 'keepDocuments.*' => 'integer|min:0|distinct',
            'confirmed' => $draft ? 'nullable|boolean' : 'accepted',
        ]);
        $files = $request->file('documents', []);
        if (count($files) !== count($data['documentMeta'] ?? [])) throw ValidationException::withMessages(['documents'=>'Choose a document type for every uploaded file.']);
        $stored = []; $removed = [];
        try {
            DB::transaction(function () use ($request, $service, $field, $data, $files, $schema, $draft, &$stored, &$removed) {
                $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
                $existing = ProviderApplication::where('user_id', $user->id)->where('service', $service)->first();
                if ($user->$field?->value === 'pending' && $existing && $existing->status === 'pending') throw ValidationException::withMessages(['application'=>'This application is under review.']);
                if ($user->$field?->value === 'approved' && $draft) throw ValidationException::withMessages(['application'=>'Submit updated verification for review; approved applications cannot be replaced by a draft.']);
                if ($existing && !hash_equals($existing->revision(), $data['version'] ?? '')) throw ValidationException::withMessages(['application'=>'This application changed. Reload before saving again.']);
                $previous = $existing?->documents ?? [];
                $documents = [];
                foreach ($data['keepDocuments'] ?? [] as $index) {
                    if (!array_key_exists($index, $previous)) throw ValidationException::withMessages(['documents'=>'A saved document is no longer available. Reload the application.']);
                    $documents[] = $previous[$index];
                }
                if (count($documents) + count($files) > 10) throw ValidationException::withMessages(['documents'=>'Keep at most 10 documents in this application.']);
                foreach ($files as $index => $file) {
                    $path = $file->store('provider-documents/'.$user->id, 'local');
                    if (!$path) throw new \RuntimeException('Unable to store verification document.');
                    $meta = $data['documentMeta'][$index];
                    $stored[] = ['path'=>$path, 'name'=>basename($file->getClientOriginalName()), 'type'=>$meta['type'], 'reference'=>$meta['reference'] ?? null, 'expiresAt'=>$meta['expiresAt'] ?? null];
                    $documents[] = $stored[array_key_last($stored)];
                }
                if (!$draft) $this->validateDocuments($documents, $schema);
                $keptPaths = array_column($documents, 'path');
                $removed = array_filter($previous, fn ($doc) => !in_array($doc['path'], $keptPaths, true));
                ProviderApplication::updateOrCreate(['user_id'=>$user->id,'service'=>$service], [
                    'status'=>$draft ? 'draft' : 'pending', 'display_name'=>$data['displayName'], 'phone'=>$data['phone'] ?? '',
                    'area'=>$data['area'] ?? '', 'description'=>$data['description'] ?? '', 'details'=>$data['details'], 'documents'=>$documents,
                    'review_note'=>$draft ? $existing?->review_note : null, 'reviewer_id'=>null, 'decided_at'=>null, 'submitted_at'=>now(),
                ]);
                if (!$draft) $user->update([$field=>'pending']);
            });
        } catch (\Throwable $error) {
            foreach ($stored as $document) Storage::disk('local')->delete($document['path']);
            throw $error;
        }
        foreach ($removed as $document) Storage::disk('local')->delete($document['path']);
        if ($request->is('api/*')) return response()->json(['data'=>ProviderApplication::where('user_id',$request->user()->id)->where('service',$service)->firstOrFail()->toReviewData(true)], 201);
        return redirect('/provider-applications/'.$service)->with('success', $draft ? 'Application draft saved.' : 'Application submitted for review.');
    }
    private function validateDocuments(array $documents, array $schema): void {
        foreach ($schema as $type=>$rule) {
            if ($rule['required'] && !collect($documents)->contains(fn ($doc) => ($doc['type'] ?? '') === $type)) throw ValidationException::withMessages(['documents'=>'Please provide: '.$rule['label'].'.']);
        }
        foreach ($documents as $document) {
            if (!empty($document['expiresAt']) && $document['expiresAt'] < now()->toDateString()) throw ValidationException::withMessages(['documents'=>'Replace expired document: '.$document['name']]);
            if (!Storage::disk('local')->exists($document['path'])) throw ValidationException::withMessages(['documents'=>'Please upload the missing file again: '.$document['name']]);
        }
    }
    public function document(Request $request, string $id, string $index) {
        $application = ProviderApplication::findOrFail($id);
        abort_unless($request->user()->is_admin || (string) $application->user_id === (string) $request->user()->id, 403);
        abort_unless(ctype_digit($index), 404);
        $document = $application->documents[(int) $index] ?? null;
        abort_unless($document && Storage::disk('local')->exists($document['path']), 404);
        $headers = ['Cache-Control'=>'private, no-store', 'X-Content-Type-Options'=>'nosniff'];
        if ($request->boolean('preview')) {
            $mime = Storage::disk('local')->mimeType($document['path']);
            abort_unless(in_array($mime, ['image/jpeg','image/png','image/webp','application/pdf'], true), 415);
            return Storage::disk('local')->response($document['path'], $document['name'], $headers + [
                'Content-Type'=>$mime, 'Content-Security-Policy'=>"sandbox; frame-ancestors 'self'", 'X-Frame-Options'=>'SAMEORIGIN',
            ], 'inline');
        }
        return Storage::disk('local')->download($document['path'], $document['name'], $headers);
    }
    public function queue() {
        $users = User::where(fn($q) => $q->where('landlord_status','pending')->orWhere('vehicle_owner_status','pending')->orWhere('service_provider_status','pending'))->get();
        $applications = ProviderApplication::whereIn('user_id', $users->pluck('id'))->get()->keyBy(fn($a) => $a->user_id.':'.$a->service);
        $rows = [];
        foreach ($users as $user) foreach (array_keys(Marketplace::TYPES) as $service) {
            if ($user->{$this->market->statusField($service)}?->value !== 'pending') continue;
            $application = $applications[$user->id.':'.$service] ?? null;
            if ($application && $application->status !== 'pending') continue;
            $rows[] = ['userId'=>$user->id, 'name'=>$user->name, 'email'=>$user->email, 'service'=>$service,
                'application'=>($applications[$user->id.':'.$service] ?? null)?->toReviewData()];
        }
        return Inertia::render('Marketplace/Approvals', ['applications'=>$rows,'requirements'=>config('provider_verification')]);
    }
    public function decide(Request $request, string $id, string $service) {
        $field = $this->market->statusField($service);
        $data = $request->validate(['version'=>'nullable|string|size:64','status'=>['required',Rule::in(['approved','rejected'])], 'reviewNote'=>'required_if:status,rejected|nullable|string|max:2000']);
        DB::transaction(function () use ($request, $id, $service, $field, $data) {
            $user = User::whereKey($id)->lockForUpdate()->firstOrFail();
            $application = ProviderApplication::where('user_id',$id)->where('service',$service)->first();
            abort_unless($user->$field?->value === 'pending' && (!$application || $application->status === 'pending'),422,'No pending application.');
            if ($application && !hash_equals($application->revision(), $data['version'] ?? '')) throw ValidationException::withMessages(['application'=>'This application changed. Reload it before reviewing.']);
            if (!$application && $data['status'] === 'approved') {
                throw ValidationException::withMessages(['application'=>'This older request has no supporting documents. Ask the user to complete their application.']);
            }
            if ($data['status'] === 'approved') $this->validateDocuments($application->documents ?? [], config('provider_verification.'.$service, []));
            $user->update([$field=>$data['status']]);
            $application?->update(['status'=>$data['status'], 'review_note'=>$data['reviewNote'] ?? null, 'reviewer_id'=>$request->user()->id, 'decided_at'=>now()]);
        });
        return back()->with('success','Application reviewed.');
    }
}
