<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class CalendarEvent extends Model
{
    use HasUlids;

    protected $fillable = ['title', 'event_date', 'event_time', 'note'];

    protected function casts(): array
    {
        return ['event_date' => 'date'];
    }
}
