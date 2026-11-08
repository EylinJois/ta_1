<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaterialCompletion extends Model
{
    use SoftDeletes;

    protected $fillable = ['user_id', 'material_id', 'completed_at'];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function material() { return $this->belongsTo(Material::class); }
}