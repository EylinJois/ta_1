<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Section;
use App\Models\Quiz;
use App\Models\QuestionBank;
use App\Models\Attempt;
use App\Models\Activity;

class QuizController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Section $section)
    {
        $this->authorizeManageSection($section);
        $course = $section->course;
        return view('quiz.create', compact(
            'section',
            'course'
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'section_id' => 'required|exists:sections,id',
            'name' => 'required',
            'description' => 'nullable',
            'duration' => 'nullable|numeric',
            'visibility' => 'nullable|boolean',
            'max_attempt' => 'nullable|numeric',
            'pass_score' => 'nullable|numeric',
            'shuffle_questions' => 'nullable|boolean',
            'shuffle_options' => 'nullable|boolean',
            'opened_at' => 'nullable|date',
            'due_at' => 'nullable|date|after:opened_at',
        ]);
        $section = Section::findOrFail($validated['section_id']);
        $this->authorizeManageSection($section);
        $duration = $request->duration
            ? $request->duration * 60
            : null;

        $quiz = Quiz::create([
            'section_id' => $section->id,
            'name' => $request->name,
            'description' => $request->description,
            'duration' => $duration,
            'visibility' => $request->visibility,
            'max_attempt' => $request->max_attempt,
            'pass_score' => $request->pass_score,
            'shuffle_questions' => $request->shuffle_questions,
            'shuffle_options' => $request->shuffle_options,
            'opened_at' => $request->opened_at,
            'due_at' => $request->due_at,
        ]);

        if ($quiz->opened_at) {
            $activity = Activity::firstOrNew([
                'source_type' => 'quiz',
                'source_id' => $quiz->id,
                'name' => 'Quiz Opened',
            ]);

            $isNew = !$activity->exists;

            $activity->fill([
                'description' => $quiz->name . ' is opened on ' . $quiz->opened_at->format('d M Y, H:i'),
                'starts_at' => $quiz->opened_at,
                'type' => 'quiz',
            ])->save();

            if ($isNew) {
                foreach ($quiz->section->course->classes as $class) {
                    $activity->participants()->syncWithoutDetaching($class->users()->pluck('users.id')->toArray());
                }
            }
        }

        if ($quiz->due_at) {
            $activity = Activity::firstOrNew([
                'source_type' => 'quiz',
                'source_id' => $quiz->id,
                'name' => 'Quiz Due',
            ]);

            $isNew = !$activity->exists;

            $activity->fill([
                'description' => $quiz->name . ' is due on ' . $quiz->due_at->format('d M Y, H:i'),
                'starts_at' => $quiz->due_at,
                'type' => 'quiz',
            ])->save();

            if ($isNew) {
                foreach ($quiz->section->course->classes as $class) {
                    $activity->participants()->syncWithoutDetaching($class->users()->pluck('users.id')->toArray());
                }
            }
        }
        return redirect()->route('quizzes.edit', $quiz->id);
    }

    /**
     * Display the specified resource.
     */
    private function courseFor(Quiz $quiz)
    {
        return $quiz->section->course;
    }

    private function authorizeManage(Quiz $quiz)
    {
        $this->authorize('update', $quiz);
    }

    private function authorizeManageSection(Section $section)
    {
        $this->authorize('create', [Quiz::class, $section]);
    }

    public function attempts(Request $request, Quiz $quiz)
    {
        $this->authorize('viewAttempts', $quiz);

        $status = $request->input('status', 'all'); // all | in_progress | submitted | graded
        $search = $request->input('search');

        $attempts = Attempt::with('user')
            ->where('quiz_id', $quiz->id)
            ->when($status !== 'all', function ($q) use ($status) {
                $q->where('status', $status);
            })
            ->when($search, function ($q) use ($search) {
                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('started_at')
            ->get();

        // Summary stats across ALL attempts for this quiz, unaffected by the
        // status/search filters above — these describe the quiz as a whole.
        $allAttempts = $status === 'all' && !$search
            ? $attempts
            : Attempt::where('quiz_id', $quiz->id)->get();

        $totalAttempts = $allAttempts->count();
        $uniqueStudents = $allAttempts->pluck('user_id')->unique()->count();
        $gradedScores = $allAttempts->whereIn('status', ['submitted', 'graded'])->pluck('score')->filter();
        $averageScore = $gradedScores->isNotEmpty() ? $gradedScores->avg() : null;
        $passCount = $quiz->pass_score
            ? $gradedScores->filter(fn($s) => $s >= $quiz->pass_score)->count()
            : null;

        return view('quiz.stats', compact(
            'quiz',
            'attempts',
            'status',
            'search',
            'totalAttempts',
            'uniqueStudents',
            'averageScore',
            'passCount',
        ));
    }

    public function show(Quiz $quiz)
    {
        $user = auth()->user();
        $course = $quiz->section->course;

        $this->authorize('view', $quiz);

        $isManager = $user->isManagerInCourse($course->id);

        $attempts = Attempt::where('quiz_id', $quiz->id)
            ->where('user_id', $user->id)
            ->orderByDesc('attempt_no')
            ->get();

        $inProgressAttempt = $attempts->firstWhere('status', 'in_progress');

        // Only submitted/graded attempts count against the limit — an
        // in-progress one is still "in use," not yet spent.
        $completedCount = $attempts->whereIn('status', ['submitted', 'graded'])->count();

        $attemptsRemaining = $quiz->max_attempt
            ? max(0, $quiz->max_attempt - $completedCount)
            : null; // null = unlimited

        $now = now();
        $notYetOpen = $quiz->opened_at && $now->lt($quiz->opened_at);
        $isClosed   = $quiz->due_at && $now->gt($quiz->due_at);
        $isOpen     = !$notYetOpen && !$isClosed;


        $canAttempt = $isManager || is_null($inProgressAttempt)
            ? (is_null($quiz->max_attempt) || $attemptsRemaining > 0)
            : true; // an in-progress attempt always allows resuming regardless of the limit

        $bestScore = $attempts->whereIn('status', ['submitted', 'graded'])->max('score');

        return view('quiz.show', compact(
            'quiz',
            'attempts',
            'inProgressAttempt',
            'attemptsRemaining',
            'canAttempt',
            'bestScore',
            'isManager',
            'isOpen',
            'notYetOpen',
            'isClosed',
        ));
    }
    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $quiz = Quiz::findOrFail($id);
        $this->authorizeManage($quiz);

        $course = $this->courseFor($quiz);

        $questionBanks = QuestionBank::where(function ($q) use ($course) {
            $q->where('course_id', $course->id)
                ->orWhereNull('course_id'); // independent banks, usable in any course
        })
            ->where(function ($q) {
                $q->where('created_by', auth()->id())
                    ->orWhere('is_public', 1);
            })
            ->withCount('questions')
            ->get();

        return view('quiz.manage', compact('quiz', 'questionBanks'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Quiz $quiz)
    {
        $this->authorize('update', $quiz);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'visibility' => 'required|boolean',
            'max_attempt' => 'nullable|integer|min:1|max:255',
            'duration_minutes' => 'nullable|integer|min:1',
            'pass_score' => 'nullable|numeric|min:0|max:100',
            'shuffle_questions' => 'required|boolean',
            'shuffle_options' => 'required|boolean',
            'opened_at' => 'nullable|date',
            'due_at' => 'nullable|date|after:opened_at',
        ]);

        $quiz->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'visibility' => $validated['visibility'],
            'max_attempt' => $validated['max_attempt'] ?? null,
            'duration' => !empty($validated['duration_minutes']) ? $validated['duration_minutes'] * 60 : null,
            'pass_score' => $validated['pass_score'] ?? null,
            'shuffle_questions' => $validated['shuffle_questions'],
            'shuffle_options' => $validated['shuffle_options'],
            'opened_at' => $validated['opened_at'] ?? null,
            'due_at' => $validated['due_at'] ?? null,
        ]);

        if ($quiz->opened_at) {
            $activity = Activity::firstOrNew([
                'source_type' => 'quiz',
                'source_id' => $quiz->id,
                'name' => 'Quiz Opened',
            ]);

            $isNew = !$activity->exists;

            $activity->fill([
                'description' => $quiz->name . ' is opened on ' . $quiz->opened_at->format('d M Y, H:i'),
                'starts_at' => $quiz->opened_at,
                'type' => 'quiz',
            ])->save();

            if ($isNew) {
                foreach ($quiz->section->course->classes as $class) {
                    $activity->participants()->syncWithoutDetaching($class->users()->pluck('users.id')->toArray());
                }
            }
        }

        if ($quiz->due_at) {
            $activity = Activity::firstOrNew([
                'source_type' => 'quiz',
                'source_id' => $quiz->id,
                'name' => 'Quiz Due',
            ]);

            $isNew = !$activity->exists;

            $activity->fill([
                'description' => $quiz->name . ' is due on ' . $quiz->due_at->format('d M Y, H:i'),
                'starts_at' => $quiz->due_at,
                'type' => 'quiz',
            ])->save();

            if ($isNew) {
                foreach ($quiz->section->course->classes as $class) {
                    $activity->participants()->syncWithoutDetaching($class->users()->pluck('users.id')->toArray());
                }
            }
        }

        return back()->with('success', 'Quiz updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $quiz = Quiz::findOrFail($id);
        $this->authorizeManage($quiz);

        $quiz->delete();

        return back()->with('success', 'Quiz deleted.');
    }
}
