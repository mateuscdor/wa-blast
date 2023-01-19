<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'number_id',
        'last_sent_at',
        'last_user_id',
        'target_number',
        'device_number',
        'defined_name',
    ];

    protected $casts = [
        'last_sent_at' => 'datetime'
    ];

    public function number(){
        return $this->belongsTo(Number::class);
    }

    public function group(){
        return $this->belongsTo(ConversationGroup::class, 'group_id');
    }

    public function chats(){
        return $this->hasMany(Chat::class)->distinct('message_id')->with(['autoreplyMessage', 'user'])->orderBy('sent_at')->where('message', '!=', ['text'=>'']);
    }
    public function unreadChats(){
        return $this->hasMany(Chat::class)->where('read_status', "UNREAD")->where('message', '!=', ['text'=>'']);
    }

    public function getLatestTimeAttribute(){
        return Carbon::parse($this->updated_at);
    }

    public function getOldestTimeAttribute(){
        $item = $this->chats()->whereNotNull('sent_at')->oldest('sent_at')->first();

        $item = $item->sent_at ?? $this->updated_at ?? '-';
        $now = new \DateTime("now", new \DateTimeZone( config('app.timezone')));

        if($item && $item !== '-'){
            return Carbon::make($item)->addSeconds($now->getOffset())->format('Y-m-d H:i:s');
        }
        return Carbon::parse($this->updated_at) ?? null;
    }

    public function getCanSendMessageAttribute()
    {
        $user = Auth::user();
        $lastSentAt = $this->last_sent_at;
        if (Carbon::now()->subMinutes(env('LIVE_CHAT_MAX_TIME', 5))->diffInMinutes($lastSentAt, false) >= 0) {
            return !$this->last_user_id || $this->last_user_id === $user->id;
        }
        return true;
    }

    public function getGroupUsersAttribute(){
        $user = Auth::user();

        $number = $this->number;
        $admin = User::with([
            'createdUsers' => function($q){
                $q->where('level_id', Level::LEVEL_CUSTOMER_SERVICE);
            }
        ])->find($number->user_id);
        if(!$admin){
            return collect([]);
        }
        return $admin->createdUsers->merge([$admin]);
    }

    public function getHasAccessAttribute(){
        $user = Auth::user();

        if(in_array($user->level_id, [Level::LEVEL_ADMIN, Level::LEVEL_SUPER_ADMIN, Level::LEVEL_RESELLER])){
            return $this->number && $this->number->user_id === $user->id;
        }
        return $user->registered_by === $this->number->user_id;
    }
}
