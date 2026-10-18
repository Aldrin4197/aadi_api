<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavedSearch extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'user_id',
        'name',
        'query',
    ];

    protected function casts(): array
    {
        return [
            'query' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
