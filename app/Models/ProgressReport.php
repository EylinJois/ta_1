<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProgressReport extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'course_id',
        'title',
        'start_period',
        'end_period',
        'progress',
        'avg_all_score',
        'comment',
        'created_by',
        'origin_name',
    ];

    protected $casts = [
        'start_period' => 'date',
        'end_period' => 'date'
    ];
    public function user() { return $this->belongsTo(User::class); }
    public function course() { return $this->belongsTo(Course::class); }
}