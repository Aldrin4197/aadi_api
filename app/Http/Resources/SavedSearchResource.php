<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SavedSearchResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'userId' => (string) $this->user_id,
            'name' => $this->name,
            'query' => $this->query,
            'createdAt' => $this->created_at->toIso8601String(),
        ];
    }
}
