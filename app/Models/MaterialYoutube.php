<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaterialYoutube extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'material_id';

    public $incrementing = false;

    protected $keyType = 'int';
    protected $fillable = ['material_id', 'url', 'video_id', 'start_sec', 'end_sec'];

    public function material(){
        return $this->belongsTo(Material::class);
    }
}
