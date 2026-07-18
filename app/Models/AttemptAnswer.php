<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

// app/Models/AttemptAnswer.php
class AttemptAnswer extends Model
{
    protected $fillable = [
        'quiz_question_id', 'attempt_id', 'feedback', 'answer',
        'select_option_id', 'score', 'answered_at',
    ];

    protected $casts = [
        'answered_at' => 'datetime',
    ];

    public function attempt() { return $this->belongsTo(Attempt::class); }
    public function quizQuestion() { return $this->belongsTo(QuizQuestion::class); }
    public function selectedOption() { return $this->belongsTo(QuestionOption::class, 'select_option_id'); }

    public function selectedOptions()
    {
        return $this->belongsToMany(QuestionOption::class, 'attempt_answer_options', 'attempt_answer_id', 'question_option_id');
    }

    public function mediaAssets()
    {
        return $this->belongsToMany(MediaAsset::class, 'attempt_answer_media', 'attempt_answer_id', 'media_asset_id')
            ->withTimestamps();
    }
}