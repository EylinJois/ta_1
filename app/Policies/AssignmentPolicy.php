<?php

namespace App\Policies;

use App\Models\Assignment;
use App\Models\Section;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AssignmentPolicy
{
    public function view(User $user, Assignment $assignment): Response
    {
        $courseId = $assignment->section->course_id;

        if (!$user->isEnrolledInCourse($courseId)) {
            return Response::deny();
        }

        if (
            !$assignment->visibility
            && !$user->isManagerInCourse($courseId)
        ) {
            return Response::denyAsNotFound();
        }

        return Response::allow();
    }

    public function create(User $user, Section $section): bool
    {
        return $user->isManagerInCourse($section->course_id);
    }

    public function update(User $user, Assignment $assignment): bool
    {
        return $user->isManagerInCourse($assignment->section->course_id);
    }

    public function delete(User $user, Assignment $assignment): bool
    {
        return $this->update($user, $assignment);
    }
}
