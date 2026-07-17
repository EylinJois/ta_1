<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AttemptAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'quiz_question_id',
        'attempt_id',
        'feedback',
        'answer',
        'select_option_id',
        'media_asset_id',
        'score',
        'answered_at'
    ];

    protected $casts = [
        'answered_at' => 'datetime'
    ];

    public function attempt()
    {
        return $this->belongsTo(Attempt::class);
    }

    public function question(){
        return $this->belongsTo(QuizQuestion::class);
    }
}