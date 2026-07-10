<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialPage extends Model
{
    use HasFactory;
     protected $fillable = ['material_id','content'];


    public function material(){
        return $this->belongsTo(Material::class);
    }
}
