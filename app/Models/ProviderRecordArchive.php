<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class ProviderRecordArchive extends Model
{
    use HasUlids;

    protected $fillable = ['provider_id', 'service', 'data', 'retain_until'];

    protected function casts(): array
    {
        return ['data' => 'array', 'retain_until' => 'datetime'];
    }
}
