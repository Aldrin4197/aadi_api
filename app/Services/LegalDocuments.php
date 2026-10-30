<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;

class LegalDocuments
{
    public function bundle(): array {
        return json_decode(file_get_contents(config('legal.bundle')), true, 512, JSON_THROW_ON_ERROR);
    }
    public function enabled(): bool { return (bool) config('legal.enabled'); }
    public function published(): bool {
        $bundle=$this->bundle();
        return ($bundle['status'] ?? '') === 'published'
            && !empty($bundle['operator']['name']) && !empty($bundle['operator']['address'])
            && filter_var($bundle['operator']['email'] ?? '', FILTER_VALIDATE_EMAIL)
            && !empty($bundle['effectiveDate'])
            && !str_contains(json_encode($bundle), '[CONFIRM');
    }
    public function document(string $context): array {
        abort_unless(in_array($context,['account','spaces','haul','raketeer'],true),404);
        $bundle=$this->bundle();
        return ['version'=>$bundle['version'],'status'=>$bundle['status'],'effectiveDate'=>$bundle['effectiveDate'],
            'operator'=>$bundle['operator'],'context'=>$context,'terms'=>$bundle['terms'],'privacy'=>$bundle['privacy'],
            'provider'=>$context==='account'?[]:$bundle['providers'][$context]];
    }
    public function digest(array $document): string { return hash('sha256',json_encode($document,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)); }
    public function accepted(string $userId,string $context): bool {
        $document=$this->document($context);
        return DB::table('legal_acceptances')->where('user_id',$userId)->where('context',$context)
            ->where('version',$document['version'])->where('content_hash',$this->digest($document))->exists();
    }
}
