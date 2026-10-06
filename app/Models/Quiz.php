<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    use HasFactory;

    protected $fillable = [
        'section_id',
        'name',
        'description',
        'visibility',
        'max_attempt',
        'duration',
        'pass_score',
        'shuffle_questions',
        'shuffle_options',
        'opened_at',
        'due_at'
    ];

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function questions()
    {
        return $this->belongsToMany(Question::class, 'quiz_questions')
            ->withPivot('id', 'point_override', 'order_index')
            ->withTimestamps()
            ->orderBy('quiz_questions.order_index');
    }

    public function attempts()
    {
        return $this->hasMany(Attempt::class);
    }
}
