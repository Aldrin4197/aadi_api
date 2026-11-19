<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class AccountReport extends Model
{
    use HasUlids;

    protected $fillable = ['reporter_id', 'reported_id', 'reason'];

    public function reporter() { return $this->belongsTo(User::class, 'reporter_id'); }
    public function reported() { return $this->belongsTo(User::class, 'reported_id'); }
    public function reviewer() { return $this->belongsTo(User::class, 'reviewed_by'); }
    public function reviews() { return $this->hasMany(AccountReportReview::class)->orderBy('id'); }
}
