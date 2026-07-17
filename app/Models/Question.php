<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;
    protected $casts = [
        'file_allowed' => 'array',
    ];
    protected $fillable = [
        'question_bank_id',
        'question',
        'type',
        'point',
        'explanation',
        'order_index',
        'file_allowed',
        'max_file_size',
        'max_file'
    ];

    public function questionBank()
    {
        return $this->belongsTo(QuestionBank::class);
    }

    public function quizzes()
    {
        return $this->belongsToMany(Quiz::class, 'quiz_questions')
            ->withPivot('id', 'point_override', 'order_index')
            ->withTimestamps();
    }

    // public function answers()
    // {
    //     return $this->hasMany(QuestionAnswer::class);
    // }

    public function acceptedAnswers()
    {
        return $this->hasMany(QuestionAcceptedAnswer::class);
    }

    public function options()
    {
        return $this->hasMany(QuestionOption::class);
    }
}
