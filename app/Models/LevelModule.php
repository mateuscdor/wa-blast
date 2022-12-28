<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LevelModule extends Model
{
    use HasFactory;

    protected $fillable = [
       'level_id',
       'module_id'
    ];

    public function level(){
        return $this->belongsTo(Level::class);
    }

    public function module(){
        return $this->belongsTo(Module::class);
    }
}
