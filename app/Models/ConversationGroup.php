<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ConversationGroup extends Model
{
    use HasFactory;

    protected $fillable = [
      'label',
      'user_id',
      'order'
    ];

    public function conversations(){
        return $this->hasMany(Conversation::class, 'group_id');
    }

    public function creator(){
        return $this->belongsTo(User::class,'user_id');
    }

    public function getIsUpdatableAttribute(){
        $user = Auth::user();
        if(!$user->has_live_chat){
            return false;
        }
        if(in_array($user->level_id, [Level::LEVEL_SUPER_ADMIN, Level::LEVEL_RESELLER])){
            return $user->id === $this->user_id;
        }
        $admin = $user;
        if($user->level_id === Level::LEVEL_CUSTOMER_SERVICE){
            $admin = $user->creator && $user->creator->level_id === Level::LEVEL_ADMIN? $user->creator: null;
        }
        if(!$admin){
            return false;
        }

        return $admin->id === $this->user_id || !!$admin->createdUsers()->find($this->user_id);
    }
}
