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

    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(
            Clas::class,
            'class_user_role',
            'user_id',
            'class_id'
        )
            ->using(ClassUserRole::class)
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

    public function activities()
    {
        return $this->belongsToMany(Activity::class, 'calendar_event_participants', 'user_id', 'activity_id')
            ->withPivot('status', 'deleted_at')
            ->withTimestamps();
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(Attempt::class);
    }

    public function questionBanks(): HasMany
    {
        return $this->hasMany(QuestionBank::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isAdmin(int $classId): bool
    {
        return $this->classes()
            ->where('classes.id', $classId)
            ->wherePivot('role', 'admin')
            ->exists();
    }

    public function isTeacher(int $classId): bool
    {
        return $this->classes()
            ->where('classes.id', $classId)
            ->wherePivot('role', 'teacher')
            ->exists();
    }

    public function isStudent(int $classId): bool
    {
        return $this->classes()
            ->where('classes.id', $classId)
            ->wherePivot('role', 'student')
            ->exists();
    }

    public function managedClasses()
    {
        return $this->classes()->wherePivotIn('role', ['admin', 'teacher']);
    }

    public function hasRoleInCourse(int $courseId, array|string $roles): bool
    {
        return $this->classes()
            ->where('classes.course_id', $courseId)
            ->wherePivotIn('role', (array) $roles)
            ->exists();
    }

    public function isAdminInCourse(int $courseId): bool
    {
        return $this->hasRoleInCourse($courseId, 'admin');
    }

    public function isManagerInCourse(int $courseId): bool
    {
        return $this->hasRoleInCourse($courseId, ['admin', 'teacher']);
    }
}
