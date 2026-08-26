<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseUserRole extends Pivot
{
    use HasFactory;

    protected $table = 'course_user_role';

    protected $fillable = [
        'user_id',
        'course_id',
        'role',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isTeacher(): bool
    {
        return $this->role === 'Teacher';
    }

    public function isStudent(): bool
    {
        return $this->role === 'Student';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'Admin';
    }
}