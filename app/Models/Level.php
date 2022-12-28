<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Level extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'max_api',
    ];

    const LEVEL_SUPER_ADMIN = 1;
    const LEVEL_RESELLER = 2;
    const LEVEL_ADMIN = 3;
    const LEVEL_CUSTOMER_SERVICE = 4;

    public function levelModules(){
        return $this->hasMany(LevelModule::class);
    }

    public function users(){
        return $this->hasMany(User::class);
    }

}
