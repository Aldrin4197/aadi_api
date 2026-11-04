<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportTicketMessage extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['emailed' => 'boolean'];
    }

    public function ticket(): BelongsTo { return $this->belongsTo(SupportTicket::class, 'ticket_id'); }
    public function author(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
}
