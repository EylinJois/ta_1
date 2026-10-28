<?php

namespace App\Policies;

use App\Models\Assignment;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SubmissionPolicy
{
    public function create(User $user, Assignment $assignment): Response
    {
        $courseId = $assignment->section->course_id;

        if (!$user->isEnrolledInCourse($courseId)) {
            return Response::deny();
        }

        if (
            !$assignment->visibility
            && $assignment->created_by !== $user->id
            && !$user->isManagerInCourse($courseId)
        ) {
            return Response::denyAsNotFound();
        }

        return Response::allow();
    }

    public function viewAny(User $user, Assignment $assignment): bool
    {
        return $user->isManagerInCourse($assignment->section->course_id);
    }

    public function view(User $user, Submission $submission): bool
    {
        $courseId = $submission->assignment->section->course_id;

        return $user->isEnrolledInCourse($courseId)
            && (
                $submission->user_id === $user->id
                || $user->isManagerInCourse($courseId)
            );
    }

    public function update(User $user, Submission $submission): bool
    {
        return $submission->user_id === $user->id
            && $user->isEnrolledInCourse($submission->assignment->section->course_id);
    }

    public function grade(User $user, Submission $submission): bool
    {
        return $user->isManagerInCourse($submission->assignment->section->course_id);
    }
}
