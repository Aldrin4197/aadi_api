<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountDeletion extends Model
{
    public const GRACE_DAYS = 30;
    public const RETENTION_MONTHS = 6;

    public const USER_REASONS = [
        'not_using' => 'I no longer use Aadi',
        'found_what_needed' => 'I found what I needed',
        'privacy' => 'Privacy concerns',
        'too_many_notifications' => 'Too many emails or notifications',
        'bad_experience' => 'I had a bad experience',
        'technical_problems' => 'Technical problems',
        'other' => 'Something else',
    ];

    /** Reasons only an administrator can record; they share one analytics breakdown with the user reasons. */
    public const ADMIN_REASONS = [
        'admin_policy_violation' => 'Policy violation',
        'admin_fraud' => 'Fraud or scams',
        'admin_spam' => 'Spam or abuse',
        'admin_requested' => 'Requested by the account owner',
        'admin_other' => 'Other (administrator)',
    ];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'scheduled_for' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'retain_until' => 'datetime',
        ];
    }

    public static function labels(): array
    {
        return self::USER_REASONS + self::ADMIN_REASONS;
    }
}
