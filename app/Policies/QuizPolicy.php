<?php

namespace App\Policies;

use App\Models\Quiz;
use App\Models\Section;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class QuizPolicy
{
    public function view(User $user, Quiz $quiz): Response
    {
        if (!$user->isEnrolledInCourse($quiz->section->course_id)) {
            return Response::deny();
        }

        if (!$quiz->visibility && !$user->isManagerInCourse($quiz->section->course_id)) {
            return Response::denyAsNotFound();
        }

        return Response::allow();
    }

    public function create(User $user, Section $section): bool
    {
        return $user->isManagerInCourse($section->course_id);
    }

    public function update(User $user, Quiz $quiz): bool
    {
        return $user->isManagerInCourse($quiz->section->course_id);
    }

    public function delete(User $user, Quiz $quiz): bool
    {
        return $this->update($user, $quiz);
    }

    public function viewAttempts(User $user, Quiz $quiz): bool
    {
        return $user->isManagerInCourse($quiz->section->course_id);
    }
}
