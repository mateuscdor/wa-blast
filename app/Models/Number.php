<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Number extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'body',
        'webhook',
        'status',
        'messages_sent',
        'api_key',
        'live_chat',
    ];

    const STATUS_CONNECTED = 'Connected';
    const STATUS_DISCONNECTED = 'Disconnect';

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function autoreplies(){
        return $this->hasMany(Autoreply::class,'device','body');
    }

    public function getIsUsableAttribute(){
        $user = Auth::user();
        if(in_array($user->level_id, [Level::LEVEL_SUPER_ADMIN, Level::LEVEL_RESELLER, Level::LEVEL_ADMIN])){
            return $this->user_id === $user->id;
        }
        $creator = $user->creator;
        if($creator && $creator->level_id === Level::LEVEL_ADMIN && $this->live_chat){
            return $creator->id === $this->user_id;
        }
        return $this->user_id === $user->id;
    }

    public function getIsUpdatableAttribute(){
        return $this->user_id === Auth::user()->id;
    }

    public function getLiveChatUsableAttribute(){
        return $this->live_chat && $this->is_usable;
    }
}
