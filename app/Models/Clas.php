<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Clas extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'name',
        'course_id',
    ];

    protected $table = 'classes';

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'class_user_role',
            'class_id',
            'user_id'
        )
            ->using(ClassUserRole::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function teachers()
    {
        return $this->users()
            ->wherePivot('role', 'teacher');
    }

    public function students()
    {
        return $this->users()
            ->wherePivot('role', 'student');
    }
    
}