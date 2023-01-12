<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;
    protected $fillable = ['user_id','tag_id','name','number', 'document_id', 'raw_values'];

    protected $casts = [
        'raw_values' => 'json'
    ];

    public function tag(){
        return $this->belongsTo(Tag::class);
    }
    public function document(){
        return $this->belongsTo(Document::class);
    }
}
