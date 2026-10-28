<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuizQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuizQuestionController extends Controller
{
    private function authorizeManage(Quiz $quiz)
    {
        $this->authorize('update', $quiz);
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

    public function available(Request $request, Quiz $quiz)
    {
        $this->authorizeManage($quiz);
        
        if (!$request->filled('question_bank_id')) {
            return response()->json([]);
        }

        $attachedIds = $quiz->questions()->pluck('questions.id');

        $questions = Question::query()
            ->whereNotIn('id', $attachedIds)
            ->whereHas('questionBank', function ($q) {
                $q->where('created_by', auth()->id())
                    ->orWhere('is_public', 1);
            })
            ->where('question_bank_id', $request->question_bank_id)
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

    public function destroy(Quiz $quiz, Question $question)
    {
        $this->authorizeManage($quiz);

        $quizQuestion = QuizQuestion::where('quiz_id', $quiz->id)
            ->where('question_id', $question->id)
            ->firstOrFail();

        $quizQuestion->delete();

        $remaining = $quiz->questions()->orderBy('quiz_questions.order_index')->get();

        foreach ($remaining as $index => $q) {
            DB::table('quiz_questions')
                ->where('quiz_id', $quiz->id)
                ->where('question_id', $q->id)
                ->update(['order_index' => $index + 1]);
        }

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

        if ($request->expectsJson()) {
            return response()->json(['status' => 'ok']);
        }

        return back()->with('success', 'Points updated.');
    }
}
