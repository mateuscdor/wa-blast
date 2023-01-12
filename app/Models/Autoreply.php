<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
class Autoreply extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    protected $casts = [
       'settings' => 'json',
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function messages(){
        return $this->hasMany(AutoreplyMessages::class);
    }
}
