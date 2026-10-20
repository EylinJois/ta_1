<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuizQuestion;
use App\Models\QuestionAcceptedAnswer;
use App\Models\QuestionOption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class QuizQuestionController extends Controller
{
    private function authorizeManage(Quiz $quiz)
    {
        $course = $this->courseFor($quiz);
        $user = Auth::user();

        abort_unless($user->isManagerInCourse($course->id), 403);
    }

    // ASSUMPTION: adjust this if Quiz reaches Course a different way.
    private function courseFor(Quiz $quiz)
    {
        return $quiz->section->course;
    }

    private function storeOptions(Question $question, Request $request)
    {
        $optionTexts = $request->input('option_text', []);
        $scorePercentages = array_map('floatval', $request->input('score_percent', []));

        foreach (array_values($optionTexts) as $index => $text) {
            $percent = $scorePercentages[$index] ?? 0;

            QuestionOption::create([
                'question_id' => $question->id,
                'text' => $text,
                'score_percent' => $percent,
                'order_index' => $index,
            ]);
        }
    }

    private function storeAcceptedAnswers(Question $question, Request $request)
    {
        $answers = $request->input('accepted_answers', []);
        $ignoreCase = $request->input('ignore_case', []);
        $ignoreSpace = $request->input('ignore_space', []);

        foreach ($answers as $index => $answer) {
            QuestionAcceptedAnswer::create([
                'question_id' => $question->id,
                'answer' => $answer,
                'ignore_case' => (bool) ($ignoreCase[$index] ?? 0),
                'ignore_space' => (bool) ($ignoreSpace[$index] ?? 0),
            ]);
        }
    }

    private function extractFileConfig(Request $request, string $type): array
    {
        if (!in_array($type, ['file', 'both'])) {
            return ['file_allowed' => null, 'max_file_size' => null, 'max_files' => null];
        }

        $allowed = json_decode($request->input('file_allowed_json', '[]'), true) ?: [];

        return [
            'file_allowed' => array_values(array_unique($allowed)),
            'max_file_size' => $request->input('max_file_size'),
            'max_files' => $request->input('max_files'),
        ];
    }

    public function storeNew(Request $request, Quiz $quiz)
    {
        $this->authorizeManage($quiz);

        $validated = $request->validate([
            'question' => 'required|string',
            'type' => 'required|in:choice,multiple_select,written,short_answer,file,both',
            'point' => 'nullable|numeric|min:0',

            'option_text' => 'required_if:type,choice,multiple_select|array|min:2',
            'option_text.*' => 'required|string|max:1000',
            'is_correct' => 'required_if:type,choice,multiple_select|array|min:1',
            'is_correct.*' => 'integer|min:0',

            'accepted_answers' => 'required_if:type,short_answer,both|array|min:1',
            'accepted_answers.*' => 'required|string|max:255',
            'ignore_case' => 'nullable|array',
            'ignore_case.*' => 'boolean',
            'ignore_space' => 'nullable|array',
            'ignore_space.*' => 'boolean',

            'file_allowed_json' => 'nullable|string',
            'max_file_size' => 'nullable|integer|min:1|max:1024',
            'max_files' => 'nullable|integer|min:1|max:255',
        ]);

        if (in_array($validated['type'], ['choice', 'multiple_select'])) {

            $optionCount = count($request->input('option_text', []));
            $correctIndexes = array_map('intval', $request->input('is_correct', []));

            foreach ($correctIndexes as $index) {
                if ($index < 0 || $index >= $optionCount) {
                    return back()->withErrors(['is_correct' => 'Invalid option marked as correct.'])->withInput();
                }
            }

            if ($validated['type'] === 'choice' && count($correctIndexes) !== 1) {
                return back()->withErrors(['is_correct' => 'Multiple choice questions need exactly one correct option.'])->withInput();
            }

            if ($validated['type'] === 'multiple_select' && count($correctIndexes) < 1) {
                return back()->withErrors(['is_correct' => 'Select at least one correct option.'])->withInput();
            }
        }
        $order_idx = QuizQuestion::where('quiz_id', $quiz->id)->max('order_index');
        $request->merge([
            'order_idx' => $order_idx + 1
        ]);

        DB::transaction(function () use ($request, $validated, $quiz) {

            $fileConfig = $this->extractFileConfig($request, $validated['type']);

            $question = Question::create([
                'question_bank_id' => null,
                'question' => $validated['question'],
                'type' => $validated['type'],
                'point' => $validated['point'] ?? 0,
                'order_index' => $request['order_idx'],
                'file_allowed' => $fileConfig['file_allowed'],
                'max_file_size' => $fileConfig['max_file_size'],
                'max_files' => $fileConfig['max_files'],
            ]);

            switch ($validated['type']) {
                case 'choice':
                case 'multiple_select':
                    $this->storeOptions($question, $request);
                    break;
                case 'short_answer':
                case 'both':
                    $this->storeAcceptedAnswers($question, $request);
                    break;
            }

            $nextOrder = (int) $quiz->questions()->max('quiz_questions.order_index') + 1;

            $quiz->questions()->attach($question->id, [
                'order_index' => $nextOrder,
            ]);
        });

        return back()->with('success', 'Question added to quiz.');
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
