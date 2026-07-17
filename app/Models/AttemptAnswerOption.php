<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttemptAnswerOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'attempt_answer_id',
        'question_option_id',
    ];

    public function attemptAnswer()
    {
        return $this->belongsTo(AttemptAnswer::class);
    }

    public function questionOption()
    {
        return $this->belongsTo(QuestionOption::class);
    }
}
