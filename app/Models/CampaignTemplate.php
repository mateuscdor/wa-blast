<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampaignTemplate extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'campaign_id',
        'template_id'
    ];

    public function campaign(){
        return $this->belongsTo(Campaign::class);
    }

    public function template(){
        return $this->belongsTo(UserTemplate::class, 'template_id');
    }
}
