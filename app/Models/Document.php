<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'filename', 'slug', 'path', 'size', 'mimetype', 'type', 'extension', 'created_by'
    ];

    protected $hidden = [
        'id',
        'created_at',
        'updated_at',
        'path',
        'type',
        'created_by',
    ];

    public function getFullPathAttribute(){
        return $this->path . $this->slug . '.' . $this->extension;
    }
}
