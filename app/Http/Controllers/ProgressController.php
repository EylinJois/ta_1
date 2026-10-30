<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\ProgressReport;
use App\Models\User;
use App\Models\Attempt;
use App\Models\Submission;
use Illuminate\Http\Request;
use App\Services\ProgressCalculationService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Laravel\Prompts\Progress;
use Illuminate\Support\Facades\Log;

class ProgressController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $reports = ProgressReport::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('course_id');

        $enrolledCourseIds = $user->classes()
            ->wherePivotIn('role', ['admin', 'teacher', 'student'])
            ->pluck('classes.course_id');

        $courseIds = $reports->keys()
            ->merge($enrolledCourseIds)
            ->unique()
            ->values();

        $courses = Course::withTrashed()
            ->whereIn('id', $courseIds)
            ->get()
            ->keyBy('id');

        $groups = $courseIds->mapWithKeys(function ($courseId) use ($courses, $reports) {
            return [$courseId => [
                'course' => $courses->get($courseId),
                'reports' => $reports->get($courseId, collect()),
            ]];
        });

        return view('progress.index-student', compact('groups'));
    }

    public function courseIndex($courseId, $studentId, $fromCourse = false)
    {
        $user = auth()->user();

        $student = User::findOrFail($studentId);

        $url = 'progress.index';

        if ($fromCourse) {
            $isManager = $user->isManagerInCourse($courseId);
            $course = Course::findOrFail($courseId);
            $url = 'courses.participants.index';
            abort_unless($isManager || $user->id === $student->id, 403);
            if(!$isManager){
                $url = 'courses.show';
            }
        } else {
            $isManager = false;
            abort_unless($user->id === $student->id, 403);
            $course = Course::withTrashed()->findOrFail($courseId);
        }

        $reports = ProgressReport::where('course_id', $courseId)
            ->where('user_id', $studentId)
            ->orderByDesc('updated_at')
            ->get();

        $quizIds = DB::table('quizzes')
            ->join('sections', 'sections.id', '=', 'quizzes.section_id')
            ->where('sections.course_id', $courseId)
            ->pluck('quizzes.id');

        $assignmentIds = DB::table('assignments')
            ->join('sections', 'sections.id', '=', 'assignments.section_id')
            ->where('sections.course_id', $courseId)
            ->pluck('assignments.id');

        $attempts = Attempt::where('user_id', $studentId)
            ->whereIn('quiz_id', $quizIds)
            ->with(['quiz' => fn($q) => $q->withTrashed()])
            ->orderByDesc('created_at')
            ->get();

        $submissions = Submission::where('user_id', $studentId)
            ->whereIn('assignment_id', $assignmentIds)
            ->with(['assignment' => fn($q) => $q->withTrashed()])
            ->orderByDesc('submitted_at')
            ->get();


        return view('progress.show', compact(
            'course',
            'student',
            'reports',
            'isManager',
            'attempts',
            'submissions',
            'fromCourse',
            'url',
        ));
    }

    public function calculate($courseId, $studentId, ProgressCalculationService $calculator)
    {
        $user = auth()->user();
        abort_unless($user->isManagerInCourse($courseId), 403);

        $student = User::findOrFail($studentId);
        $course = Course::findOrFail($courseId);

        try {
        return response()->json($calculator->calculate($student, $course));
    } catch (\Throwable $e) {
        Log::error('Progress calculation failed', [
            'course_id'  => $course->id,
            'student_id' => $student->id,
            'error'      => $e->getMessage(),
            'trace'      => $e->getTraceAsString(),
        ]);

        return response()->json([
            'message' => config('app.debug')
                ? $e->getMessage()
                : 'Could not calculate progress. Check the server log.',
        ], 500);
    }
    }

    public function store(Request $request, Course $course, ProgressCalculationService $calculator)
    {
        $user = auth()->user();
        abort_unless($user->isManagerInCourse($course->id), 403);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'start_period' => 'nullable|date',
            'end_period' => 'nullable|date|after_or_equal:start_period',
            'progress' => 'nullable|integer|min:0|max:100',
            'comment' => 'nullable|string',
        ]);

        $student = User::findOrFail($validated['user_id']);

        if (is_null($validated['progress'])) {
            $calculated = $calculator->calculate($student, $course);
            $validated['progress'] = $validated['progress'] ?? $calculated['progress'];
        }

        $className = $student->classes()
            ->where('course_id', $course->id)
            ->value('name');

        $originName = Str::limit(
            $className ? $course->name . ' - ' . $className : $course->name,
            100,
            ''
        );

        ProgressReport::create(array_merge($validated, [
            'course_id'   => $course->id,
            'origin_name' => $originName,
            'created_by'  => $user->id,
        ]));

        return back()->with('success', 'Progress report added.');
    }
}
