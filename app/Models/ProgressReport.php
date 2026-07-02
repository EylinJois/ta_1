<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProgressReport extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'course_id',
        'title',
        'start_period',
        'end_period',
        'progress',
        'avg_quiz_score',
        'comment',
        'created_by'
    ];

    protected $casts = [
        'start_period' => 'date',
        'end_period' => 'date'
    ];
}