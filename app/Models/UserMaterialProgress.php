<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserMaterialProgress extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'material_id',
        'progress_percent',
        'completed_at',
        'completion_count',
        'study_duration_seconds'
    ];

    protected $casts = [
        'completed_at' => 'datetime'
    ];
}