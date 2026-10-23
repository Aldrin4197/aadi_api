<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class InquiryMessage extends Model
{
    use HasUlids;
    protected $guarded = ['id'];
    public function toData(): array {
        return ['id'=>$this->id, 'senderId'=>$this->sender_id, 'body'=>$this->body, 'createdAt'=>$this->created_at->toIso8601String()];
    }
}
