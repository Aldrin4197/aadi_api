<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SupportTicket extends Model
{
    use HasUlids;

    public const CATEGORIES = [
        'account' => 'Account & login',
        'listing' => 'A listing or booking',
        'provider' => 'Provider application',
        'report' => 'Report a listing or user',
        'bug' => 'Something is not working',
        'feedback' => 'Feedback or an idea',
        'other' => 'Something else',
    ];
    public const STATUSES = ['open' => 'Open', 'in_progress' => 'In progress', 'resolved' => 'Resolved', 'closed' => 'Closed'];
    public const PRIORITIES = ['low' => 'Low', 'normal' => 'Normal', 'high' => 'High', 'urgent' => 'Urgent'];
    public const ACTIVE = ['open', 'in_progress'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['first_response_at' => 'datetime', 'resolved_at' => 'datetime', 'last_activity_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $ticket) {
            $ticket->reference ??= self::newReference();
            $ticket->last_activity_at ??= now();
        });
    }

    /** Short, readable and free of look-alike characters, e.g. AAD-7K3QX9. */
    public static function newReference(): string
    {
        do {
            $reference = 'AAD-'.collect(str_split(Str::upper(Str::random(40))))->filter(fn ($c) => str_contains('ABCDEFGHJKMNPQRSTUVWXYZ23456789', $c))->take(6)->implode('');
        } while (strlen($reference) < 10 || self::where('reference', $reference)->exists());

        return $reference;
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function assignee(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
    public function messages(): HasMany { return $this->hasMany(SupportTicketMessage::class, 'ticket_id')->oldest(); }
}
