<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\Question;
use App\Models\QuestionBank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class QuizQuestionController extends Controller
{
    private function authorizeManage(Quiz $quiz)
    {
        $course = $this->courseFor($quiz);
        $user = Auth::user();

        abort_unless(
            $user->isAdmin($course->id) || $user->isTeacher($course->id),
            403
        );
    }

    // ASSUMPTION: adjust this if Quiz reaches Course a different way.
    private function courseFor(Quiz $quiz)
    {
        return $quiz->section->course;
    }

    public function index(Quiz $quiz)
    {
        $this->authorizeManage($quiz);

        $course = $this->courseFor($quiz);

        $questionBanks = QuestionBank::where(function ($q) use ($course) {
            $q->where('course_id', $course->id)
                ->orWhereNull('course_id'); // independent banks, usable in any course
        })
            ->where(function ($q) {
                $q->where('created_by', auth()->id())
                    ->orWhere('visibility', 1);
            })
            ->withCount('questions')
            ->get();

        return view('quiz.questions.manage', compact('quiz', 'questionBanks'));
    }

    /**
     * JSON search endpoint powering the "pick manually" panel — returns
     * questions not already on this quiz, filtered by bank/type/search text.
     */
    public function available(Request $request, Quiz $quiz)
    {
        $this->authorizeManage($quiz);

        $attachedIds = $quiz->questions()->pluck('questions.id');

        $questions = Question::query()
            ->whereNotIn('id', $attachedIds)
            ->whereHas('questionBank', function ($q) {
                $q->where('created_by', auth()->id())
                    ->orWhere('visibility', 1);
            })
            ->when($request->filled('question_bank_id'), function ($q) use ($request) {
                $q->where('question_bank_id', $request->question_bank_id);
            })
            ->when($request->filled('type'), function ($q) use ($request) {
                $q->where('type', $request->type);
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where('question', 'like', '%' . $request->search . '%');
            })
            ->orderBy('id', 'desc')
            ->limit(50)
            ->get(['id', 'question', 'type', 'point', 'question_bank_id']);

        return response()->json($questions);
    }

    public function store(Request $request, Quiz $quiz)
    {
        $this->authorizeManage($quiz);

        $validated = $request->validate([
            'question_ids' => 'required|array|min:1',
            'question_ids.*' => 'required|integer|exists:questions,id',
        ]);

        DB::transaction(function () use ($quiz, $validated) {

            $alreadyAttached = $quiz->questions()->pluck('questions.id')->toArray();
            $toAttach = array_diff($validated['question_ids'], $alreadyAttached);

            $nextOrder = (int) $quiz->questions()->max('quiz_questions.order_index') + 1;

            foreach ($toAttach as $questionId) {
                $quiz->questions()->attach($questionId, [
                    'order_index' => $nextOrder++,
                ]);
            }
        });

        return back()->with('success', 'Questions added to quiz.');
    }

    public function storeRandom(Request $request, Quiz $quiz)
    {
        $this->authorizeManage($quiz);

        $validated = $request->validate([
            'question_bank_id' => 'required|exists:question_banks,id',
            'count' => 'required|integer|min:1|max:100',
            'type' => 'nullable|in:choice,multiple_select,written,short_answer,file,both',
        ]);

        $attachedCount = DB::transaction(function () use ($quiz, $validated) {

            $alreadyAttached = $quiz->questions()->pluck('questions.id');

            $candidateIds = Question::where('question_bank_id', $validated['question_bank_id'])
                ->whereNotIn('id', $alreadyAttached)
                ->when($validated['type'] ?? null, function ($q) use ($validated) {
                    $q->where('type', $validated['type']);
                })
                ->inRandomOrder()
                ->limit($validated['count'])
                ->pluck('id');

            $nextOrder = (int) $quiz->questions()->max('quiz_questions.order_index') + 1;

            foreach ($candidateIds as $questionId) {
                $quiz->questions()->attach($questionId, [
                    'order_index' => $nextOrder++,
                ]);
            }

            return $candidateIds->count();
        });

        if ($attachedCount === 0) {
            return back()->with('warning', 'No matching questions were available to add.');
        }

        return back()->with('success', "{$attachedCount} question(s) added to quiz.");
    }

    public function destroy(Quiz $quiz, Question $question)
    {
        $this->authorizeManage($quiz);

        DB::transaction(function () use ($quiz, $question) {

            $quiz->questions()->detach($question->id);

            // Re-sequence remaining order_index values so there's no gap
            // left where the removed question used to sit.
            $remaining = $quiz->questions()->orderBy('quiz_questions.order_index')->get();

            foreach ($remaining as $index => $q) {
                DB::table('quiz_questions')
                    ->where('quiz_id', $quiz->id)
                    ->where('question_id', $q->id)
                    ->update(['order_index' => $index + 1]);
            }
        });

        return back()->with('success', 'Question removed from quiz.');
    }

    public function reorder(Request $request, Quiz $quiz)
    {
        $this->authorizeManage($quiz);

        $validated = $request->validate([
            'question_ids' => 'required|array',
            'question_ids.*' => 'required|integer|exists:questions,id',
        ]);

        DB::transaction(function () use ($quiz, $validated) {
            foreach ($validated['question_ids'] as $index => $questionId) {
                DB::table('quiz_questions')
                    ->where('quiz_id', $quiz->id)
                    ->where('question_id', $questionId)
                    ->update(['order_index' => $index + 1]);
            }
        });

        return response()->json(['status' => 'ok']);
    }

    public function updatePointOverride(Request $request, Quiz $quiz, Question $question)
    {
        $this->authorizeManage($quiz);

        $validated = $request->validate([
            'point_override' => 'nullable|numeric|min:0',
        ]);

        DB::table('quiz_questions')
            ->where('quiz_id', $quiz->id)
            ->where('question_id', $question->id)
            ->update(['point_override' => $validated['point_override']]);

        return response()->json(['status' => 'ok']);
    }
}
