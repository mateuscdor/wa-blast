<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConversationTakeOver extends Model
{
    use HasFactory;

    protected $fillable = [
        'target_id',
        'from_id',
        'message'
    ];

    public function target(){
        return $this->belongsTo(User::class, 'target_id');
    }
    public function messanger(){
        return $this->belongsTo(User::class, 'from_id');
    }

}
