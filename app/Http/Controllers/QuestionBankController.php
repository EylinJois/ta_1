<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\QuestionBank;
use App\Models\Question;
use App\Models\Course;

class QuestionBankController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $filter = $request->input('filter', 'all'); // all | mine | others
        $search = $request->input('search');

        $questionBanks = QuestionBank::with([
            'creator',
            'course' => fn($q) => $q->withTrashed(),
        ])
            ->withCount('questions')
            ->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                    ->orWhere('is_public', 1);
            })
            ->when($filter === 'mine', function ($q) use ($user) {
                $q->where('created_by', $user->id);
            })
            ->when($filter === 'others', function ($q) use ($user) {
                $q->where('created_by', '!=', $user->id);
            })
            ->when($search, function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->get();

        $courses = Course::whereHas('classes.users', fn($query) => $query->where('users.id', auth()->id()))
            ->with('classes.users')
            ->get();

        return view('quiz.bank.index', compact('questionBanks', 'courses', 'filter', 'search'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'course_id' => 'nullable|exists:courses,id',
            'is_public' => 'required|boolean',
        ]);

        $this->authorize('create', [QuestionBank::class, isset($validated['course_id']) ? (int) $validated['course_id'] : null]);

        QuestionBank::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'course_id' => $validated['course_id'] ?? null,
            'created_by' => auth()->id(),
            'is_public' => $validated['is_public'],
        ]);

        return redirect()->route('questionbank.index')->with('success', 'Question Bank created successfully.');
    }

    public function reorder(Request $request, QuestionBank $questionBank)
    {
        $this->authorize('manageQuestions', $questionBank);

        $request->validate([
            'questions' => 'required|array',
            'questions.*.id' => 'required|exists:questions,id',
            'questions.*.order_idx' => 'required|integer',
        ]);

        foreach ($request->questions as $item) {
            Question::where('id', $item['id'])
                ->where('question_bank_id', $questionBank->id) // guard against reordering another bank's question
                ->update(['order_index' => $item['order_idx']]);
        }

        return response()->noContent();
    }

    public function show($id)
    {
        $questionBank = QuestionBank::findOrFail($id);
        $this->authorize('view', $questionBank);

        $questionBank->load('questions');
        $user = auth()->user();
        $courses = Course::whereHas('classes.users', fn($query) => $query->where('users.id', $user->id))
            ->with('classes.users')
            ->get();

        return view('quiz.bank.show', compact('questionBank', 'courses'));
    }

    public function update($id, Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'course_id' => 'nullable|exists:courses,id',
            'visibility' => 'required_without:is_public|boolean',
            'is_public' => 'required_without:visibility|boolean',
        ]);

        $questionBank = QuestionBank::findOrFail($id);
        $courseId = isset($validated['course_id']) ? (int) $validated['course_id'] : null;
        $this->authorize('update', [$questionBank, $courseId]);

        $isPublic = $validated['is_public'] ?? $validated['visibility'] ?? null;
        $questionBank->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'course_id' => $validated['course_id'] ?? null,
            'is_public' => $isPublic,
        ]);

        return redirect()->route('questionbank.index')->with('success', 'Question Bank updated successfully.');
    }

    public function destroy($id)
    {
        $questionBank = QuestionBank::findOrFail($id);
        $this->authorize('delete', $questionBank);
        $questionBank->delete();
        return redirect()->route('questionbank.index')->with('success', 'Question Bank deleted successfully.');
    }
}
