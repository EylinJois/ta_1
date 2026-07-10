<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialYoutube extends Model
{
    use HasFactory;

    protected $fillable = ['material_id', 'url', 'video_id', 'start_sec', 'end_sec'];

    public function material(){
        return $this->belongsTo(Material::class);
    }
}
