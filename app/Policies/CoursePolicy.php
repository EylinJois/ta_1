<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    public function view(User $user, Course $course): bool
    {
        return $user->isEnrolledInCourse($course->id);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Course $course): bool
    {
        return $user->isAdminInCourse($course->id);
    }

    public function delete(User $user, Course $course): bool
    {
        return $this->update($user, $course);
    }

    public function manageParticipants(User $user, Course $course): bool
    {
        return $user->isAdminInCourse($course->id);
    }

    public function manageClasses(User $user, Course $course): bool
    {
        return $user->isAdminInCourse($course->id);
    }
}
