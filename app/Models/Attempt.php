<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attempt extends Model
{
    use HasFactory;

    protected $table = 'attempts';

    protected $fillable = [
        'quiz_id',
        'user_id',
        'score',
        'started_at',
        'submitted_at',
        'attempt_no',
        'status'
    ];
    
    protected $casts = [
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    public function getDurationSecondsAttribute()
    {
        if (!$this->submitted_at) {
            return null; // still in progress — no duration yet
        }

        return $this->started_at->diffInSeconds($this->submitted_at);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function answers()
    {
        return $this->hasMany(AttemptAnswer::class);
    }

}
