<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialAsset extends Model
{
    use HasFactory;

    protected $fillable = [
        'material_id',
        'media_asset_id',
    ];
    public function material(){
        return $this->belongsTo(Material::class);
    }

    public function mediaAsset(){
        return $this->belongsTo(MediaAsset::class);
    }
}
