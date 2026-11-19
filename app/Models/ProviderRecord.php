<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProviderRecord extends Model
{
    use HasUlids;

    protected $fillable = ['provider_id', 'customer_id', 'request_id', 'source_viewing_id', 'service', 'listing_id', 'listing_title', 'kind', 'status', 'starts_on', 'ends_on', 'agreed_cents'];

    protected function casts(): array
    {
        return ['starts_on' => 'date', 'ends_on' => 'date', 'agreed_cents' => 'integer'];
    }

    public function customer(): BelongsTo { return $this->belongsTo(User::class, 'customer_id'); }
    public function payments(): HasMany { return $this->hasMany(ProviderPayment::class); }
    public function tenancy(): HasMany { return $this->hasMany(self::class, 'source_viewing_id'); }
}
