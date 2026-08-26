<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\ProgressReport;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\ProgressCalculationService;

class ProgressController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $courses = $user->courses()->with('users')->get();
        $reports = ProgressReport::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();
        return view('progress.index-student', compact('courses', 'reports'));
    }

    public function courseIndex(Course $course, $studentId)
    {
        $user = auth()->user();

        $student = User::findOrFail($studentId);
        $isManager = $user->isAdmin($course->id) || $user->isTeacher($course->id);

        // abort_unless(
        //     $isManager || $user->id === $student->id,
        //     403
        // );

        $reports = ProgressReport::where('course_id', $course->id)
            ->where('user_id', $studentId)
            ->orderByDesc('updated_at') // sort by date updated
            ->get();

        return view('progress.show', compact('course', 'student', 'reports', 'isManager'));
    }

    public function calculate(Course $course, User $student, ProgressCalculationService $calculator)
    {
        $user = auth()->user();
        abort_unless($user->isAdmin($course->id) || $user->isTeacher($course->id), 403);

        return response()->json($calculator->calculate($student, $course));
    }

    public function store(Request $request, Course $course, ProgressCalculationService $calculator)
    {
        $user = auth()->user();
        abort_unless($user->isAdmin($course->id) || $user->isTeacher($course->id), 403);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'start_period' => 'nullable|date',
            'end_period' => 'nullable|date|after_or_equal:start_period',
            'progress' => 'nullable|integer|min:0|max:100',
            'avg_quiz_score' => 'nullable|numeric|min:0',
            'comment' => 'nullable|string',
        ]);

        // Auto-calculate whichever numeric field the teacher left blank,
        // rather than requiring manual entry every time.
        if (is_null($validated['progress']) || is_null($validated['avg_quiz_score'])) {

            $student = \App\Models\User::findOrFail($validated['user_id']);
            $calculated = $calculator->calculate($student, $course);

            $validated['progress'] = $validated['progress'] ?? $calculated['progress'];
            $validated['avg_quiz_score'] = $validated['avg_quiz_score'] ?? $calculated['avg_quiz_score'];
        }

        ProgressReport::create(array_merge($validated, [
            'course_id' => $course->id,
            'created_by' => $user->id,
        ]));

        return back()->with('success', 'Progress report added.');
    }
}
