<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ListingConversation extends Model
{
    use HasUlids;

    protected $fillable = ['service', 'listing_id', 'listing_title', 'customer_id', 'provider_id'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ListingMessage::class);
    }
}
