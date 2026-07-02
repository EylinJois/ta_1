<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'is_admin',
        'img',
        'is_active',
        'password',
        'email_verified_at',
        'remember_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_admin' => 'boolean',
        'is_active' => 'boolean',
        'email_verified_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(
            Course::class,
            'course_user_role'
        )
        ->using(CourseUserRole::class)
        ->withPivot('role')
        ->withTimestamps();
    }

    public function createdMaterials(): HasMany
    {
        return $this->hasMany(
            Material::class,
            'created_by'
        );
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function certifications(): HasMany
    {
        return $this->hasMany(UserCertification::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isAdmin(): bool
    {
        return $this->is_admin;
    }

    public function isTeacherInCourse(int $courseId): bool
    {
        return $this->courses()
            ->where('course_id', $courseId)
            ->wherePivot('role', 'Teacher')
            ->exists();
    }

    public function isStudentInCourse(int $courseId): bool
    {
        return $this->courses()
            ->where('course_id', $courseId)
            ->wherePivot('role', 'Student')
            ->exists();
    }
}