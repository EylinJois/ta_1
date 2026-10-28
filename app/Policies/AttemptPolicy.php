<?php

namespace App\Policies;

use App\Models\Attempt;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AttemptPolicy
{
    public function create(User $user, Quiz $quiz): Response
    {
        $courseId = $quiz->section->course_id;

        if (!$user->isEnrolledInCourse($courseId)) {
            return Response::deny();
        }

        if (!$quiz->visibility && !$user->isManagerInCourse($courseId)) {
            return Response::denyAsNotFound();
        }

        return Response::allow();
    }

    public function view(User $user, Attempt $attempt): bool
    {
        $courseId = $attempt->quiz->section->course_id;

        return $user->isEnrolledInCourse($courseId)
            && ($attempt->user_id === $user->id || $user->isManagerInCourse($courseId));
    }

    public function update(User $user, Attempt $attempt): bool
    {
        return $attempt->user_id === $user->id
            && $user->isEnrolledInCourse($attempt->quiz->section->course_id);
    }

    public function grade(User $user, Attempt $attempt): bool
    {
        return $user->isManagerInCourse($attempt->quiz->section->course_id);
    }
}
