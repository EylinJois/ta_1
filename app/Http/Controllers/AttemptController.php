<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attempt;
use App\Models\Quiz;
use App\Models\Question;
use App\Models\AttemptAnswer;
use Illuminate\Support\Facades\DB;
use App\Models\MediaAsset;
use Illuminate\Support\Facades\Storage;

class AttemptController extends Controller
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
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Quiz $quiz)
    {
        $user = auth()->user();
        $this->authorize('create', [Attempt::class, $quiz]);
        $isManager = $user->isManagerInCourse($quiz->section->course_id);

        if (!$isManager) {
            if ($quiz->opened_at && now()->lt($quiz->opened_at)) {
                return back()->with('error', 'This quiz is not open yet.');
            }
            if ($quiz->due_at && now()->gt($quiz->due_at)) {
                return back()->with('error', 'This quiz is closed.');
            }
        }

        $existingInProgress = Attempt::where('quiz_id', $quiz->id)
            ->where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->first();

        if ($existingInProgress) {
            // Resume rather than create a duplicate.
            return redirect()->route('attempts.take', $existingInProgress);
        }

        $completedCount = Attempt::where('quiz_id', $quiz->id)
            ->where('user_id', $user->id)
            ->whereIn('status', ['submitted', 'graded'])
            ->count();

        if ($quiz->max_attempt && $completedCount >= $quiz->max_attempt) {
            return back()->with('error', 'You have used all your attempts for this quiz.');
        }

        $attempt = Attempt::create([
            'quiz_id' => $quiz->id,
            'user_id' => $user->id,
            'attempt_no' => $completedCount + 1,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        return redirect()->route('attempts.take', $attempt);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /*
    |--------------------------------------------------------------------------
    | Authorization helpers
    |--------------------------------------------------------------------------
    */

    // True once the quiz timer or due date has passed (30s grace for network lag)
    private function attemptExpired(Attempt $attempt): bool
    {
        $quiz = $attempt->quiz;
        $grace = 30;

        if ($quiz->duration && $attempt->started_at->copy()->addSeconds($quiz->duration + $grace)->isPast()) {
            return true;
        }

        return $quiz->due_at && $quiz->due_at->copy()->addSeconds($grace)->isPast();
    }

    // The quiz_question must belong to this attempt's quiz
    private function assertQuestionInQuiz(Attempt $attempt, int $quizQuestionId): void
    {
        abort_unless(
            DB::table('quiz_questions')
                ->where('id', $quizQuestionId)
                ->where('quiz_id', $attempt->quiz_id)
                ->exists(),
            422
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Taking an attempt
    |--------------------------------------------------------------------------
    */

    public function take(Attempt $attempt)
    {
        $this->authorize('update', $attempt);

        if ($attempt->status !== 'in_progress') {
            return redirect()->route('quizzes.show', $attempt->quiz_id)
                ->with('error', 'This attempt has already been submitted.');
        }

        // Time ran out while the student was away: submit what they saved.
        if ($this->attemptExpired($attempt)) {
            $this->finalizeAttempt($attempt);

            return redirect()->route('quizzes.show', $attempt->quiz_id)
                ->with('error', 'Time ran out. Your saved answers were submitted automatically.');
        }

        $quiz = $attempt->quiz;

        $quizQuestions = DB::table('quiz_questions')
            ->where('quiz_id', $quiz->id)
            ->orderBy('order_index')
            ->get();

        $questionIds = $quizQuestions->pluck('question_id');

        $questions = Question::with(['options', 'acceptedAnswers'])
            ->whereIn('id', $questionIds)
            ->get()
            ->keyBy('id');

        // Attach each question's own quiz_questions.id + point_override,
        // in the order quiz_questions defines.
        $orderedQuestions = $quizQuestions->map(function ($qq) use ($questions) {
            $question = $questions->get($qq->question_id);
            if (!$question) return null;
            $question->quiz_question_id = $qq->id;
            $question->effective_point = $qq->point_override ?? $question->point;
            return $question;
        })->filter()->values();

        // Stable per-attempt shuffle — seeded on the attempt id so reloading
        // the page doesn't reshuffle mid-attempt.
        if ($quiz->shuffle_questions) {
            mt_srand($attempt->id);
            $orderedQuestions = $orderedQuestions->shuffle();
        }

        // Seeded per-question option shuffle (attempt id + question id).
        if ($quiz->shuffle_options) {
            $orderedQuestions = $orderedQuestions->map(function ($q) use ($attempt) {
                if (in_array($q->type, ['choice', 'multiple_select'])) {
                    mt_srand($attempt->id + $q->id);
                    $q->setRelation('options', $q->options->shuffle()->values());
                }
                return $q;
            });
        }

        $existingAnswers = AttemptAnswer::where('attempt_id', $attempt->id)
            ->with('selectedOptions')
            ->get()
            ->keyBy('quiz_question_id');

        $remainingSeconds = null;
        if ($quiz->duration) {
            $elapsed = now()->diffInSeconds($attempt->started_at);
            $remainingSeconds = max(0, $quiz->duration - $elapsed);
        }

        return view('quiz.attempt.take', compact(
            'attempt',
            'quiz',
            'orderedQuestions',
            'existingAnswers',
            'remainingSeconds'
        ));
    }

    /**
     * Autosave a single question's answer. Upserts one attempt_answers row
     * per (attempt, quiz_question). For multiple_select, also replaces the
     * attempt_answer_options rows.
     */
    public function saveAnswer(Request $request, Attempt $attempt)
    {
        $this->authorize('update', $attempt);
        abort_unless($attempt->status === 'in_progress', 422);

        $validated = $request->validate([
            'quiz_question_id' => 'required|integer|exists:quiz_questions,id',
            'answer' => 'nullable|string',
            'select_option_id' => 'nullable|integer|exists:question_options,id',
            'option_ids' => 'nullable|array',
            'option_ids.*' => 'integer|exists:question_options,id',
        ]);

        abort_if($this->attemptExpired($attempt), 422, 'Time is up.');
        $this->assertQuestionInQuiz($attempt, $validated['quiz_question_id']);

        // Submitted options must belong to this question (they carry score_percent)
        $questionId = DB::table('quiz_questions')
            ->where('id', $validated['quiz_question_id'])
            ->value('question_id');

        $validOptionIds = Question::findOrFail($questionId)->options()->pluck('id');

        $submittedOptionIds = collect($validated['option_ids'] ?? []);
        if (!empty($validated['select_option_id'])) {
            $submittedOptionIds->push($validated['select_option_id']);
        }

        abort_if($submittedOptionIds->diff($validOptionIds)->isNotEmpty(), 422);

        DB::transaction(function () use ($validated, $attempt) {

            $answer = AttemptAnswer::updateOrCreate(
                [
                    'attempt_id' => $attempt->id,
                    'quiz_question_id' => $validated['quiz_question_id'],
                ],
                [
                    'answer' => $validated['answer'] ?? null,
                    'select_option_id' => $validated['select_option_id'] ?? null,
                    'answered_at' => now(),
                ]
            );

            if (array_key_exists('option_ids', $validated)) {
                $answer->selectedOptions()->sync($validated['option_ids'] ?? []);
            }
        });

        return response()->json(['status' => 'ok']);
    }

    public function uploadAnswerFile(Request $request, Attempt $attempt)
    {
        $this->authorize('update', $attempt);
        abort_unless($attempt->status === 'in_progress', 422);

        $validated = $request->validate([
            'quiz_question_id' => 'required|integer|exists:quiz_questions,id',
            'file' => 'required|file',
        ]);

        abort_if($this->attemptExpired($attempt), 422, 'Time is up.');
        $this->assertQuestionInQuiz($attempt, $validated['quiz_question_id']);

        $file = $request->file('file');

        $quizQuestion = DB::table('quiz_questions')->find($validated['quiz_question_id']);
        $question = Question::find($quizQuestion->question_id);

        $answer = AttemptAnswer::firstOrCreate(
            ['attempt_id' => $attempt->id, 'quiz_question_id' => $validated['quiz_question_id']]
        );

        $currentCount = $answer->mediaAssets()->count();
        $maxFiles = $question->max_files ?? 1;

        if ($currentCount >= $maxFiles) {
            return response()->json([
                'message' => "You can only upload up to {$maxFiles} " . str('file')->plural($maxFiles) . " for this question.",
            ], 422);
        }

        if ($question->max_file_size) {
            $maxBytes = $question->max_file_size * 1024 * 1024;
            if ($file->getSize() > $maxBytes) {
                return response()->json(['message' => "File exceeds the {$question->max_file_size}MB limit."], 422);
            }
        }

        if ($question->file_allowed) {
            // Extension guessed from the file's real MIME type, falling back
            // to the client-supplied one if it can't be guessed.
            $ext = strtolower($file->extension() ?: $file->getClientOriginalExtension());
            if (!in_array($ext, $question->file_allowed)) {
                return response()->json(['message' => 'File type not allowed for this question.'], 422);
            }
        }

        $path = $file->store('attempt-answers', 'public');

        $mediaAsset = MediaAsset::create([
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'uploaded_by' => auth()->id(),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);

        $answer->mediaAssets()->attach($mediaAsset->id);
        $answer->update(['answered_at' => now()]);

        return response()->json([
            'status' => 'ok',
            'media_asset_id' => $mediaAsset->id,
            'file_name' => $mediaAsset->original_name,
            'files_remaining' => $maxFiles - ($currentCount + 1),
        ]);
    }

    public function deleteAnswerFile(Attempt $attempt, MediaAsset $mediaAsset)
    {
        $this->authorize('update', $attempt);
        abort_unless($attempt->status === 'in_progress', 422);

        $answer = AttemptAnswer::where('attempt_id', $attempt->id)
            ->whereHas('mediaAssets', fn($q) => $q->where('media_assets.id', $mediaAsset->id))
            ->firstOrFail();

        $answer->mediaAssets()->detach($mediaAsset->id);

        // Delete the underlying file/row too — it's not referenced anywhere
        // else once detached (each upload creates its own MediaAsset row).
        Storage::disk('public')->delete($mediaAsset->file_path);
        $mediaAsset->delete();

        return response()->json(['status' => 'ok']);
    }

    /**
     * Not blocked when expired on purpose, so the timer's auto-submit works.
     * Answers saved after expiry are already rejected in saveAnswer/upload.
     */
    public function submit(Attempt $attempt)
    {
        $this->authorize('update', $attempt);
        abort_unless($attempt->status === 'in_progress', 422);

        $this->finalizeAttempt($attempt);

        return redirect()->route('quizzes.show', $attempt->quiz_id)
            ->with('success', 'Quiz submitted.');
    }

    /**
     * Auto-grade what can be auto-graded and close the attempt.
     * Shared by submit() and the expired-timer path in take().
     */
    private function finalizeAttempt(Attempt $attempt): void
    {
        DB::transaction(function () use ($attempt) {

            $needsManualGrading = false;

            $answers = AttemptAnswer::where('attempt_id', $attempt->id)
                ->with(['quizQuestion.question.acceptedAnswers', 'quizQuestion.question.options', 'selectedOptions', 'mediaAssets'])
                ->get();

            foreach ($answers as $answer) {

                $question = $answer->quizQuestion->question;
                $points = (float) ($answer->quizQuestion->point_override ?? $question->point);

                switch ($question->type) {

                    case 'choice':
                        $selected = $question->options->firstWhere('id', (int) $answer->select_option_id);
                        $percent = max(0, min(100, $selected?->score_percent ?? 0));

                        $answer->score = round($points * $percent / 100, 2);
                        break;

                    case 'multiple_select':
                        $percent = $question->options
                            ->whereIn('id', $answer->selectedOptions->pluck('id'))
                            ->sum('score_percent');

                        $answer->score = round($points * max(0, min(100, $percent)) / 100, 2);
                        break;

                    case 'short_answer':
                        $matched = $question->acceptedAnswers->contains(function ($accepted) use ($answer) {
                            $given = $answer->answer ?? '';
                            $expected = $accepted->answer;

                            if ($accepted->ignore_case) {
                                $given = mb_strtolower($given);
                                $expected = mb_strtolower($expected);
                            }
                            if ($accepted->ignore_space) {
                                $given = preg_replace('/\s+/', '', $given);
                                $expected = preg_replace('/\s+/', '', $expected);
                            }

                            return $given === $expected;
                        });
                        $answer->score = $matched ? $points : 0;
                        break;

                    case 'written':
                    case 'file':
                    case 'both':
                        // Needs a human — leave score null for now.
                        $needsManualGrading = true;
                        break;
                }

                $answer->save();
            }

            $totalScore = $answers->whereNotNull('score')->sum('score');

            $attempt->update([
                'score' => $totalScore,
                'submitted_at' => now(),
                'status' => $needsManualGrading ? 'submitted' : 'graded',
            ]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Grading / review
    |--------------------------------------------------------------------------
    */

    public function grade(Attempt $attempt)
    {
        $this->authorize('grade', $attempt);

        abort_if($attempt->status === 'in_progress', 422, 'This attempt is still in progress and cannot be graded yet.');

        return $this->renderReview($attempt);
    }

    public function review(Attempt $attempt)
    {
        $this->authorize('view', $attempt);

        if ($attempt->status === 'in_progress') {
            return redirect()->route('quizzes.show', $attempt->quiz_id)
                ->with('error', 'This attempt is still in progress and cannot be reviewed yet.');
        }

        return $this->renderReview($attempt);
    }

    // grade() and review() render the same view with the same data.
    private function renderReview(Attempt $attempt)
    {
        $quiz = $attempt->quiz;

        $quizQuestions = DB::table('quiz_questions')
            ->where('quiz_id', $quiz->id)
            ->orderBy('order_index')
            ->get();

        $questions = Question::with(['options', 'acceptedAnswers'])
            ->whereIn('id', $quizQuestions->pluck('question_id'))
            ->get()
            ->keyBy('id');

        $orderedQuestions = $quizQuestions->map(function ($qq) use ($questions) {
            $question = $questions->get($qq->question_id);
            if (!$question) return null;
            $question->quiz_question_id = $qq->id;
            $question->effective_point = $qq->point_override ?? $question->point;
            return $question;
        })->filter()->values();

        $answers = AttemptAnswer::where('attempt_id', $attempt->id)
            ->with(['selectedOptions', 'mediaAssets', 'selectedOption'])
            ->get()
            ->keyBy('quiz_question_id');

        return view('quiz.attempt.review', compact('attempt', 'quiz', 'orderedQuestions', 'answers'));
    }

    public function saveGrade(Request $request, Attempt $attempt)
    {
        $this->authorize('grade', $attempt);

        // Build per-question max-point rules dynamically, since each question
        // has its own point value (with quiz_questions.point_override taking
        // precedence over questions.point).
        $maxPoints = DB::table('quiz_questions')
            ->join('questions', 'questions.id', '=', 'quiz_questions.question_id')
            ->where('quiz_questions.quiz_id', $attempt->quiz_id)
            ->select('quiz_questions.id as quiz_question_id', 'quiz_questions.point_override', 'questions.point')
            ->get()
            ->mapWithKeys(fn($quizq) => [$quizq->quiz_question_id => $quizq->point_override ?? $quizq->point]);

        $rules = [
            'scores' => 'required|array',
            'feedback' => 'nullable|array',
            'feedback.*' => 'nullable|string',
        ];

        foreach ($maxPoints as $quizQuestionId => $maxPoint) {
            $rules["scores.{$quizQuestionId}"] = "nullable|numeric|min:0|max:{$maxPoint}";
        }

        $validated = $request->validate($rules);

        // Only accept scores for questions that belong to this quiz
        $validIds = $maxPoints->keys()->map(fn($id) => (string) $id);
        abort_if(
            collect(array_keys($validated['scores']))->map(fn($id) => (string) $id)->diff($validIds)->isNotEmpty(),
            422
        );

        DB::transaction(function () use ($validated, $attempt) {

            foreach ($validated['scores'] as $quizQuestionId => $score) {

                AttemptAnswer::updateOrCreate(
                    ['attempt_id' => $attempt->id, 'quiz_question_id' => $quizQuestionId],
                    [
                        'score' => $score,
                        'feedback' => $validated['feedback'][$quizQuestionId] ?? null,
                    ]
                );
            }

            $totalScore = AttemptAnswer::where('attempt_id', $attempt->id)->sum('score');

            $attempt->update([
                'score' => $totalScore,
                'status' => 'graded',
            ]);
        });

        return redirect()
            ->route('quizzes.attempts.index', $attempt->quiz_id)
            ->with('success', 'Attempt graded.');
    }
}