<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class MarketplaceInquiry extends Model
{
    use HasUlids;
    protected $guarded = ['id'];
    public function customer() { return $this->belongsTo(User::class, 'customer_id'); }
    public function owner() { return $this->belongsTo(User::class, 'owner_id'); }
    public function messages() { return $this->hasMany(InquiryMessage::class, 'inquiry_id'); }
    public function latestMessage() { return $this->hasOne(InquiryMessage::class, 'inquiry_id')->latestOfMany('id'); }
    public function scopeForUser($query, User $user) {
        return $query->where(fn ($q) => $q->where('customer_id', $user->id)->orWhere('owner_id', $user->id));
    }
    public function summary(User $viewer): array {
        return [
            'id' => $this->id, 'service' => $this->service, 'listingId' => $this->listing_id,
            'listingTitle' => $this->listing_title,
            'direction' => (string) $viewer->id === (string) $this->owner_id ? 'received' : 'sent',
            'customer' => ['id' => $this->customer_id, 'name' => $this->customer?->name],
            'owner' => ['id' => $this->owner_id, 'name' => $this->owner?->name],
            'lastMessage' => $this->latestMessage?->body,
            'updatedAt' => $this->updated_at->toIso8601String(),
        ];
    }
}
