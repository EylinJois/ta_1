<?php

namespace App\Services;

use App\Models\Attempt;
use App\Models\Course;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ProgressCalculationService
{
    public function calculate(User $user, Course $course): array
    {
        $quizIds = DB::table('quizzes')
            ->join('sections', 'sections.id', '=', 'quizzes.section_id')
            ->where('sections.course_id', $course->id)
            ->pluck('quizzes.id');

        $assignmentIds = DB::table('assignments')
            ->join('sections', 'sections.id', '=', 'assignments.section_id')
            ->where('sections.course_id', $course->id)
            ->pluck('assignments.id');

        // Best graded score per quiz
        $bestQuizScores = Attempt::where('user_id', $user->id)
            ->whereIn('quiz_id', $quizIds)
            ->where('status', 'graded')
            ->selectRaw('quiz_id, MAX(score) as best_score')
            ->groupBy('quiz_id')
            ->pluck('best_score');

        // Best scored submission per assignment
        $bestAssignmentScores = Submission::where('user_id', $user->id)
            ->whereIn('assignment_id', $assignmentIds)
            ->whereNotNull('score')
            ->selectRaw('assignment_id, MAX(score) as best_score')
            ->groupBy('assignment_id')
            ->pluck('best_score');

        // Combined average across quizzes and assignments
        $allScores = $bestQuizScores->concat($bestAssignmentScores);

        $avgScore = $allScores->isNotEmpty()
            ? round($allScores->avg(), 2)
            : null;

        $total = $quizIds->count() + $assignmentIds->count();
        $completed = $bestQuizScores->count() + $bestAssignmentScores->count();
        $progress = $total > 0 ? (int) round($completed / $total * 100) : 0;

        return [
            'progress'             => $progress,
            'avg_quiz_score'       => $avgScore, 
            'avg_quiz_only'        => $bestQuizScores->isNotEmpty() ? round($bestQuizScores->avg(), 2) : null,
            'avg_assignment_only'  => $bestAssignmentScores->isNotEmpty() ? round($bestAssignmentScores->avg(), 2) : null,
        ];
    }
}