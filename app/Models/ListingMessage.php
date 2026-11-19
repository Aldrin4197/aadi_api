<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListingMessage extends Model
{
    protected $fillable = ['listing_conversation_id', 'sender_id', 'body'];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ListingConversation::class, 'listing_conversation_id');
    }
}
