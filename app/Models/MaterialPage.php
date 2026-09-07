<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaterialPage extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $primaryKey = 'material_id';

    public $incrementing = false;

    protected $keyType = 'int';
     protected $fillable = ['material_id','content'];


    public function material(){
        return $this->belongsTo(Material::class);
    }
    public function sentences() { return $this->hasMany(Sentence::class, 'material_page_id','material_id')->orderBy('order_idx'); }
}
