<?php

namespace App\Policies;

use App\Models\QuestionBank;
use App\Models\User;

class QuestionBankPolicy
{
    public function view(User $user, QuestionBank $questionBank): bool
    {
        return $questionBank->is_public
            || $questionBank->created_by === $user->id;
    }

    public function create(User $user, ?int $courseId = null): bool
    {
        return $courseId === null || $user->isEnrolledInCourse($courseId);
    }

    public function update(User $user, QuestionBank $questionBank, ?int $courseId = null): bool
    {
        return $questionBank->created_by === $user->id
            && ($courseId === null || $user->isEnrolledInCourse($courseId));
    }

    public function delete(User $user, QuestionBank $questionBank): bool
    {
        return $questionBank->created_by === $user->id;
    }

    public function manageQuestions(User $user, QuestionBank $questionBank): bool
    {
        return $questionBank->created_by === $user->id;
    }
}
