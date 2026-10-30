<?php
namespace App\Http\Controllers;
use App\Services\LegalDocuments;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class LegalController extends Controller
{
    public function show(Request $request,LegalDocuments $legal) {
        $preview=$request->boolean('preview');
        if ($preview) abort_unless($request->user()?->is_admin,403);
        else abort_unless($legal->enabled() && $legal->published(),404);
        $document=$legal->document((string)$request->query('context','account'));
        return Inertia::render('Legal/Agreement',['document'=>$document,'hash'=>$legal->digest($document),'preview'=>$preview,'readOnly'=>$request->is('legal/terms'),
            // Defer optional onboarding until this dedicated agreement flow finishes.
            'auth'=>['user'=>$request->user()?->only(['id','name','email','is_admin']), 'onboarding'=>null]]);
    }
    public function store(Request $request,LegalDocuments $legal) {
        abort_unless($legal->enabled() && $legal->published(),404);
        $data=$request->validate(['context'=>['required',Rule::in(['account','spaces','haul','raketeer'])],
            'version'=>'required|string|max:80','hash'=>'required|string|size:64','terms'=>'accepted','privacy'=>'accepted',
            'provider'=>'exclude_if:context,account|required|accepted']);
        $document=$legal->document($data['context']);
        abort_unless($data['version']===$document['version'] && hash_equals($legal->digest($document),$data['hash']),409,'The agreement changed. Reload and review the current version.');
        if ($data['context']!=='account') abort_unless($legal->accepted((string)$request->user()->id,'account'),409,'Accept the account agreement first.');
        DB::transaction(function () use ($request,$data,$document) {
            DB::table('users')->where('id',$request->user()->id)->lockForUpdate()->first();
            abort_if(DB::table('legal_acceptances')->where('context',$data['context'])->where('version',$data['version'])->where('content_hash','!=',$data['hash'])->exists(),409,'Publish changed text under a new version.');
            $key=['user_id'=>$request->user()->id,'context'=>$data['context'],'version'=>$data['version']];
            $existing=DB::table('legal_acceptances')->where($key)->first();
            abort_if($existing && !hash_equals($existing->content_hash,$data['hash']),409,'This version was edited. A new agreement version must be published.');
            if (!$existing) DB::table('legal_acceptances')->insert($key+['content_hash'=>$data['hash'],'snapshot'=>json_encode($document),'accepted_at'=>now()]);
        });
        $return=$request->session()->pull('legal.return','/dashboard');
        if (!is_string($return)||!str_starts_with($return,'/')||str_starts_with($return,'//')||str_contains($return,'\\')) $return='/dashboard';
        return redirect($return,303);
    }
}
