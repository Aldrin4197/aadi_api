<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrustBadgeResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'type' => $this->type->value,
            'status' => $this->status->value,
            'grantedAt' => $this->granted_at?->toIso8601String(),
        ];
    }
}
