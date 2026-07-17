<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionAcceptedAnswer extends Model
{
    use HasFactory;
    protected $table = 'question_accepted_answers';
    protected $fillable = ['question_id', 'answer','ignore_case','ignore_space'];

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
