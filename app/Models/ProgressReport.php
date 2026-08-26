<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProgressReport extends Model
{
    use HasFactory;

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
    public function user() { return $this->belongsTo(User::class); }
    public function course() { return $this->belongsTo(Course::class); }
}