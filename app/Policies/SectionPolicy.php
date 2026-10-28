<?php

namespace App\Policies;

use App\Models\Section;
use App\Models\User;

class SectionPolicy
{
    public function view(User $user, Section $section): bool
    {
        return $user->isEnrolledInCourse($section->course_id)
            && (
                $section->visibility
                || $user->isManagerInCourse($section->course_id)
            );
    }

    public function create(User $user, int $courseId): bool
    {
        return $user->isManagerInCourse($courseId);
    }

    public function manage(User $user, Section $section): bool
    {
        return $user->isManagerInCourse($section->course_id);
    }

    public function update(User $user, Section $section): bool
    {
        return $this->manage($user, $section);
    }

    public function delete(User $user, Section $section): bool
    {
        return $this->manage($user, $section);
    }
}
