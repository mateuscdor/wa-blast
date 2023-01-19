<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutoreplyMessages extends Model
{
    use HasFactory;
    protected $guarded = [];

    protected $casts = [
       'created_at' => 'datetime',
       'updated_at' => 'datetime'
    ];

    public function message(){
        return $this->hasOne(Chat::class, 'message_id', 'message_id');
    }
    public function repliedMessage(){
        return $this->hasOne(Chat::class, 'message_id', 'replied_to_message_id')->with('conversation');
    }
    public function autoreply(){
        return $this->belongsTo(Autoreply::class);
    }
    public function getSentAtAttribute(){

        if($this->getAttributes()['sent_at']){
            return $this->getAttributes()['sent_at'];
        }

        $item = $this->getAttributes()['sent_at'] ?: $this->message->sent_at?? '-';
        $now = new \DateTime("now", new \DateTimeZone( config('app.timezone')));

        if($item && $item !== '-'){
            return Carbon::make($item)->addSeconds($now->getOffset())->format('Y-m-d H:i:s');
        }
        return $item;
    }
    public function getReceivedAtAttribute(){

        if($this->getAttributes()['received_at']){
            return $this->getAttributes()['received_at'];
        }

        $item = $this->getAttributes()['received_at'] ?: $this->repliedMessage->sent_at?? '-';
        $now = new \DateTime("now", new \DateTimeZone( config('app.timezone')));

        if($item && $item !== '-'){
            return Carbon::make($item)->addSeconds($now->getOffset())->format('Y-m-d H:i:s');
        }
        return $item;
    }
    public function getTargetMessageAttribute(){
        return $this->repliedMessage->message ?? '-';
    }
    public function getTargetNameAttribute(){
        return $this->repliedMessage->conversation->target_name ?? $this->getAttributes()['target_name'] ?: '-';
    }
    public function getTargetLabelAttribute(){
        return $this->repliedMessage->conversation->defined_name ?? $this->getAttributes()['target_name'] ?: '-';
    }
    public function getTargetNumberAttribute(){
        return $this->repliedMessage->conversation->target_number ?? $this->getAttributes()['target_number'] ?: '-';
    }
    public function getReplyMessageAttribute(){
        return $this->message->message ?? [];
    }
}
