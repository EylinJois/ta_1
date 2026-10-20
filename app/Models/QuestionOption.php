<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuestionOption extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'question_id',
        'text',
        'score_percent',
        'order_index'
    ];

    protected $casts = [
        'score_percent' => 'integer',
    ];

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}