<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserCourseProgress extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'course_id',
        'progress_percent',
        'completed_at',
        'last_access',
        'started_at'
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'last_access' => 'datetime',
        'started_at' => 'datetime',
    ];
}