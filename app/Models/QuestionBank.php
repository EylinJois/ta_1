<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionBank extends Model
{
    use HasFactory;

    protected $fillable=['course_id','created_by','name','description','visibility'];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function created_by()
    {
        return $this->belongsTo(User::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function creator()
{
    return $this->belongsTo(User::class, 'created_by');
}
}
