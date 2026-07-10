<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialLink extends Model
{
    use HasFactory;

    protected $fillable = ['material_id','url'];

    public function material(){
        return $this->belongsTo(Material::class);
    }
}
