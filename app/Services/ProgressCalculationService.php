<?php

namespace App\Services;

use App\Models\Attempt;
use App\Models\Course;
use App\Models\User;
use App\Models\UserCourseProgress;
use Illuminate\Support\Facades\DB;

class ProgressCalculationService
{
    public function calculate(User $user, Course $course): array
    {
        $progress = UserCourseProgress::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->value('progress_percent') ?? 0;

        $quizIds = DB::table('quizzes')
            ->join('sections', 'sections.id', '=', 'quizzes.section_id')
            ->where('sections.course_id', $course->id)
            ->pluck('quizzes.id');

        $bestScoresPerQuiz = Attempt::where('user_id', $user->id)
            ->whereIn('quiz_id', $quizIds)
            ->where('status', 'graded')
            ->selectRaw('quiz_id, MAX(score) as best_score')
            ->groupBy('quiz_id')
            ->pluck('best_score');

        $avgQuizScore = $bestScoresPerQuiz->isNotEmpty()
            ? round($bestScoresPerQuiz->avg(), 2)
            : null;

        return [
            'progress' => (int) $progress,
            'avg_quiz_score' => $avgQuizScore,
        ];
    }
}