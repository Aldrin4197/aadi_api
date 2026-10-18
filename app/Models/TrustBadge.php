<?php

namespace App\Models;

use App\Enums\BadgeType;
use App\Enums\VerificationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrustBadge extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'status',
        'granted_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => BadgeType::class,
            'status' => VerificationStatus::class,
            'granted_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
