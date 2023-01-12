<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutoreplyMessages extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function message(){
        return $this->hasOne(Chat::class, 'message_id', 'message_id');
    }
    public function repliedMessage(){
        return $this->hasOne(Chat::class, 'message_id', 'replied_to_message_id')->with('conversation');
    }
    public function autoreply(){
        return $this->belongsTo(Autoreply::class);
    }
}
