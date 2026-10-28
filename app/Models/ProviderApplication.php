<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
class ProviderApplication extends Model {
    use HasUlids;
    protected $guarded = ['id'];
    protected function casts(): array { return ['details' => 'array', 'documents' => 'array', 'submitted_at' => 'datetime', 'decided_at' => 'datetime']; }
    public function user() { return $this->belongsTo(User::class); }
    public function revision(): string {
        return hash('sha256', json_encode([$this->status,$this->display_name,$this->phone,$this->area,$this->description,$this->documents,$this->details,$this->review_note,$this->updated_at?->toISOString()]));
    }
    public function toReviewData(bool $api = false): array {
        return [
            'version' => $this->revision(),
            'id' => $this->id, 'service' => $this->service, 'status' => $this->status,
            'displayName' => $this->display_name, 'phone' => $this->phone,
            'area' => $this->area, 'description' => $this->description,
            'details' => $this->details ?? [],
            'reviewNote' => $this->review_note,
            'submittedAt' => $this->submitted_at->toIso8601String(),
            'decidedAt' => $this->decided_at?->toIso8601String(),
            'documents' => collect($this->documents)->map(fn ($document, $index) => [
                'name' => $document['name'],
                'index' => $index,
                'type' => $document['type'] ?? 'supporting_document',
                'reference' => $document['reference'] ?? null,
                'expiresAt' => $document['expiresAt'] ?? null,
                'url' => ($api ? '/api/v1/provider-documents/' : '/provider-documents/').$this->id.'/'.$index,
            ])->values()->all(),
        ];
    }
}
