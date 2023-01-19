<?php

namespace App\Models;

use App\Casts\MessageCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'message_id',
        'message',
        'user_id',
        'number_type',
        'read_status',
        'sent_at',
    ];

    protected $casts = [
        'message' => MessageCast::class,
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function autoreplyMessage(){
        return $this->hasOne(AutoreplyMessages::class, 'message_id', 'message_id');
    }

    public function replierMessage(){
        return $this->hasOne(AutoreplyMessages::class, 'replied_to_message_id', 'message_id');
    }

    public function conversation(){
        return $this->belongsTo(Conversation::class);
    }

    public function getIsAutoReplyAttribute(){
        return (bool)$this->autoreplyMessage || $this->number_type === 'AUTO_REPLY';
    }
}
