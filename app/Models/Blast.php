<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Blast extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'sender',
        'campaign_id',
        'receiver',
        'message',
        'type',
        'status',
    ];

    public function getUpdatedAtAttribute($value)
    {
        return \Carbon\Carbon::parse($value)->setTimezone(auth()->user()->timezone)->format('d M Y H:i:s');
    }
}
