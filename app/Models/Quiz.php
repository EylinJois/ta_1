<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quiz extends Model
{
    use HasFactory;
    use SoftDeletes;

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

    protected $casts = [
        'visibility' => 'boolean',
        'shuffle_questions' => 'boolean',
        'shuffle_options' => 'boolean',
        'opened_at' => 'datetime',
        'due_at' => 'datetime',
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
            ->wherePivotNull('deleted_at')
            ->orderBy('quiz_questions.order_index');
    }

    public function attempts()
    {
        return $this->hasMany(Attempt::class);
    }
}
