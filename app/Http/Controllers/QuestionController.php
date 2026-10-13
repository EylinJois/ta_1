<?php

namespace App\Http\Controllers;

use App\Models\QuestionOption;
use Illuminate\Http\Request;
use App\Models\Question;
use App\Models\QuestionAcceptedAnswer;
use Illuminate\Support\Facades\DB;

class QuestionController extends Controller
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
    public function store(Request $request)
    {
        $validated = $request->validate([
            'question_bank_id' => 'required|exists:question_banks,id',
            'question' => 'required|string',
            'type' => 'required|in:choice,multiple_select,written,short_answer,file,both',
            'point' => 'nullable|numeric|min:0',
            'explanation' => 'nullable|string',

            'option_text' => 'required_if:type,choice,multiple_select|array|min:2',
            'option_text.*' => 'required|string|max:1000',
            'score_percent' => 'required_if:type,choice,multiple_select|array|min:2',
            'score_percent.*' => 'integer|min:0|max:100',

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

        // Cross-field checks the rules above can't express on their own.
        if (in_array($validated['type'], ['choice', 'multiple_select'])) {

            $optionCount = count($request->input('option_text', []));
            $scorePercentages = array_map('intval', $request->input('score_percent', []));

            foreach ($scorePercentages as $index => $percent) {
                if ($percent < 0 || $percent > 100) {
                    return back()
                        ->withErrors(['score_percent' => 'Invalid score percentage.'])
                        ->withInput();
                }
            }

            if ($optionCount !== count($scorePercentages)) {
                return back()
                    ->withErrors(['option_text' => 'The number of options and score percentages must match.'])
                    ->withInput();
            }
        }

        if (in_array($validated['type'], ['short_answer', 'both'])) {

            $correctIndexes = array_map('intval', $request->input('is_correct', []));

            foreach ($correctIndexes as $index) {
                if ($index < 0 || $index >= count($request->input('accepted_answers', []))) {
                    return back()
                        ->withErrors(['is_correct' => 'Invalid correct option index.'])
                        ->withInput();
                }
            }

            if ($validated['type'] === 'short_answer' && count($correctIndexes) !== 1) {
                return back()
                    ->withErrors(['is_correct' => 'Short answer questions need exactly one correct option.'])
                    ->withInput();
            }
        }

        $order_idx=Question::where('question_bank_id', $validated['question_bank_id'])->max('order_index');
        $request->merge([
            'order_idx' => $order_idx+1
        ]);

        $question = DB::transaction(function () use ($request, $validated) {

            $fileConfig = $this->extractFileConfig($request, $validated['type']);

            $question = Question::create([
                'question_bank_id' => $validated['question_bank_id'],
                'question' => $validated['question'],
                'type' => $validated['type'],
                'point' => $validated['point'] ?? 0,
                'explanation' => $validated['explanation'] ?? null,
                'file_allowed' => $fileConfig['file_allowed'],
                'max_file_size' => $fileConfig['max_file_size'],
                'max_files' => $fileConfig['max_files'],
                'order_index' => $request->input('order_idx'),
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

            return $question;
        });


        return redirect()
            ->route('questionbank.show', $validated['question_bank_id'])
            ->with('success', 'Question added.');
    }

    private function storeOptions(Question $question, Request $request)
    {
        $optionTexts = $request->input('option_text', []);
        $optionPercentages = array_map('intval', $request->input('score_percent', []));

        foreach (array_values($optionTexts) as $index => $text) {
            QuestionOption::create([
                'question_id' => $question->id,
                'text' => $text,
                'score_percent' => $optionPercentages[$index] ?? 0,
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
    public function update(Request $request, Question $question)
{
    $validated = $request->validate([
        'question_bank_id' => 'nullable|exists:question_banks,id', // now nullable
        'question' => 'required|string',
        'type' => 'required|in:choice,multiple_select,written,short_answer,file,both',
        'point' => 'nullable|min:0',
        'explanation' => 'nullable|string',

        'option_text' => 'required_if:type,choice,multiple_select|array|min:2',
        'option_text.*' => 'required|string|max:1000',
        'score_percent' => 'required_if:type,choice,multiple_select|array|min:2',
        'score_percent.*' => 'integer|min:0|max:100',

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

    if (is_null($question->question_bank_id) && !empty($validated['question_bank_id'])) {
        return back()->withErrors(['question_bank_id' => 'This question is not part of a question bank.']);
    }

    if (in_array($validated['type'], ['choice', 'multiple_select'])) {
        $optionCount = count($request->input('option_text', []));
        $scorePercentages = array_map('intval', $request->input('score_percent', []));

        foreach ($scorePercentages as $index => $percent) {
            if ($index < 0 || $index >= $optionCount) {
                return back()->withErrors(['score_percent' => 'Invalid option score.'])->withInput();
            }
        }

        if ($validated['type'] === 'choice' && count(array_filter($scorePercentages, function($v) { return $v === 100; })) !== 1) {
            return back()->withErrors(['score_percent' => 'Multiple choice questions need exactly one correct option.'])->withInput();
        }

    }

    DB::transaction(function () use ($request, $validated, $question) {

        $fileConfig = $this->extractFileConfig($request, $validated['type']);

        $question->update([
            'question' => $validated['question'],
            'type' => $validated['type'],
            'point' => $validated['point'] ?? 0,
            'explanation' => $validated['explanation'] ?? null,
            'file_allowed' => $fileConfig['file_allowed'],
            'max_file_size' => $fileConfig['max_file_size'],
            'max_files' => $fileConfig['max_files'],
        ]);

        $question->options()->delete();
        $question->acceptedAnswers()->delete();

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
    });

    if ($question->question_bank_id) {
        return redirect()
            ->route('questionbank.show', $question->question_bank_id)
            ->with('success', 'Question updated.');
    }

    return back()->with('success', 'Question updated.');
}


    public function destroy(Question $question)
    {
        $questionBankId = $question->question_bank_id;

        DB::transaction(function () use ($question) {
            $question->options()->delete();
            $question->acceptedAnswers()->delete();
            $question->delete();
        });

        return redirect()
            ->route('questionbank.show', $questionBankId)
            ->with('success', 'Question deleted.');
    }
}
