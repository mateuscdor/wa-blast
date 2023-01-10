<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'email',
        'password',
        'api_key',
        'chunk_blast',
        'limit_device',
        'limit_admin_account',
        'level_id',
        'package_id',
        'registered_by',
        'phone_number',
        'display_name',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function package(){
        return $this->belongsTo(Package::class);
    }
    public function level(){
        return $this->belongsTo(Level::class);
    }
    public function numbers(){
        return $this->hasMany(Number::class);
    }
    public function autoreplies(){
        return $this->hasMany(Autoreply::class);
    }
    public function contacts(){
        return $this->hasMany(Contact::class);
    }
    public function tags(){
        return $this->hasMany(Tag::class);
    }
    public function blasts(){
        return $this->hasMany(Blast::class);
    }
    public function campaigns(){
        return $this->hasMany(Campaign::class);
    }
    public function creator(){
        return $this->belongsTo(User::class, 'registered_by');
    }
    public function createdUsers(){
        return $this->hasMany(User::class, 'registered_by');
    }
    public function liveChatNumber(){
        return $this->hasOne(Number::class)->where('live_chat', 1);
    }
    public function messageTemplates(){
        return $this->hasMany(UserTemplate::class);
    }

    // get expired subscription
    public function getExpiredSubscriptionAttribute(){

        if($this->level_id === Level::LEVEL_SUPER_ADMIN){
            return "Your account has a lifetime subscription.";
        }

        $user = $this;
        if($this->level_id === Level::LEVEL_CUSTOMER_SERVICE){
            $user = $this->creator()->first();
            if(!$user){
                return "You don't have a subscription";
            }
        }

        if(in_array($user->level_id, [Level::LEVEL_SUPER_ADMIN, Level::LEVEL_RESELLER])){
            return "Your account has a lifetime subscription.";
        }

        if($user->active_subscription == 'inactive'){
            return 'You don\'t have a subscription';
        } else if($user->active_subscription == 'lifetime'){
            return 'You have a lifetime subscription';
        } else if($user->active_subscription == 'active'){
            $expired_date = $user->subscription_expired;
            $expired_date = strtotime($expired_date);
            $current_date = strtotime(date('Y-m-d'));
            if($expired_date < $current_date){
                return 'Your subscription expired';
            } else {
                // count days
                $days = $expired_date - $current_date;
                $days = $days / (60 * 60 * 24);
                $days = round($days);
                return 'Your subscription will expire in '.$days.' days';
            }
        }  
    }

    // get booliean expired subscription
    public function getIsExpiredSubscriptionAttribute(){

        if($this->level_id === Level::LEVEL_SUPER_ADMIN){
            return false;
        }

        $user = $this;
        if($this->level_id === Level::LEVEL_CUSTOMER_SERVICE){
            $user = $this->creator()->first();
            if(!$user){
                return false;
            }
            if(in_array($user->level_id, [Level::LEVEL_SUPER_ADMIN, Level::LEVEL_RESELLER])){
                return false;
            }
        }

        if($user->active_subscription == 'inactive'){
            return true;
        } else if($user->active_subscription == 'lifetime'){
            return false;
        } else if($user->active_subscription == 'active'){
            $expired_date = $user->subscription_expired;
            $expired_date = strtotime($expired_date);
            $current_date = strtotime(date('Y-m-d'));
            if($expired_date < $current_date){
                return true;
            } else {
                return false;
            }
        }  
    }

    // get total device connect and disconnect
    public function getTotalDeviceAttribute(){
         $connectedDevice = Number::whereUserId($this->id)->whereStatus(Number::STATUS_CONNECTED)->count();
         $disconnectedDevice = Number::whereUserId($this->id)->whereStatus(Number::STATUS_DISCONNECTED)->count();
         return [
             'connected' => $connectedDevice,
             'disconnected' => $disconnectedDevice,
             'max' => $this->max_device,
         ];
    }

    public function getMaxDeviceAttribute(){
        $package = $this->package;
        if($package){
            if(Level::LEVEL_ADMIN === $this->level_id){
                return $package->admin_device;
            } else {
                return $package->user_device;
            }
        }
        return $this->limit_device;
    }

    public function getCanCreateUserAttribute(){
        if($this->level_id === Level::LEVEL_SUPER_ADMIN){
            return true;
        }
        return !$this->is_expired_subscription;
    }

    public function getHasLiveChatAttribute(){
        if(in_array($this->level_id, [Level::LEVEL_SUPER_ADMIN, Level::LEVEL_RESELLER])) {
            return true;
        }
        if($this->creator){
            if(in_array($this->creator->level_id, [Level::LEVEL_SUPER_ADMIN, Level::LEVEL_RESELLER])){
                return true;
            }
        }
        if($this->is_expired_subscription){
            return false;
        }
        $package = Package::find($this->package_id);
        return $package ? $package->live_chat : false;
    }

    public function getCanAddLiveChatAttribute(){
        if(in_array($this->level_id, [Level::LEVEL_SUPER_ADMIN])){
            return true;
        }
        if(in_array($this->level_id, [Level::LEVEL_CUSTOMER_SERVICE])){
            return false;
        }
        if(Level::LEVEL_RESELLER === $this->level_id) {
            if($this->numbers()->where('live_chat', 1)->count())
                return false;
            return true;
        }
        // ADMIN:
        $package = $this->package;
        return $this->has_live_chat && $this->numbers()->where('live_chat', 1)->count() === 0;
    }

    public function getCanCreateAdminAccountAttribute(){
        $levelId = Auth::user()->level_id;
        if($levelId === Level::LEVEL_RESELLER){
            return Auth::user()->max_admin_account > Auth::user()->createdUsers()->count();
        }
        if($levelId === Level::LEVEL_SUPER_ADMIN){
            return true;
        }
        return false;
    }
}
