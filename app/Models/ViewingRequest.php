<?php

namespace App\Models;

use App\Enums\ViewingStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ViewingRequest extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'listing_id',
        'listing_title',
        'tenant_id',
        'tenant_name',
        'owner_id',
        'owner_name',
        'requested_at',
        'proposed_at',
        'status',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'status' => ViewingStatus::class,
            'requested_at' => 'datetime',
            'proposed_at' => 'datetime',
        ];
    }

    public function listing()
    {
        return $this->belongsTo(Listing::class);
    }

    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
