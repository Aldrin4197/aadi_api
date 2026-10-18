<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ViewingRequestResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'listingId' => $this->listing_id,
            'listingTitle' => $this->listing_title,
            'tenantId' => (string) $this->tenant_id,
            'tenantName' => $this->tenant_name,
            'ownerId' => (string) $this->owner_id,
            'ownerName' => $this->owner_name,
            'requestedAt' => $this->requested_at->toIso8601String(),
            'proposedAt' => $this->proposed_at?->toIso8601String(),
            'status' => $this->status->value,
            'note' => $this->note,
            'createdAt' => $this->created_at->toIso8601String(),
        ];
    }
}
