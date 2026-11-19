<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class ProviderPayment extends Model
{
    use HasUlids;

    protected $fillable = ['amount_cents', 'paid_on', 'period', 'note'];

    protected function casts(): array
    {
        return ['amount_cents' => 'integer', 'paid_on' => 'date', 'voided_at' => 'datetime'];
    }
}
