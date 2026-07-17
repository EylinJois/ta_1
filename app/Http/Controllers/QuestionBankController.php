<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\QuestionBank;
use App\Models\User;
use Auth;
use App\Models\Question;
use App\Models\Course;

class QuestionBankController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $filter = $request->input('filter', 'all'); // all | mine | others
        $search = $request->input('search');

        $questionBanks = QuestionBank::with(['creator', 'course'])
            ->withCount('questions')
            ->where(function ($q) use ($user) {
                // Own banks are always visible to their creator regardless of
                // the visibility flag; other users' banks only show if shared.
                $q->where('created_by', $user->id)
                    ->orWhere('visibility', 1);
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

        $courses = Course::all();

        return view('quiz.bank.index', compact('questionBanks', 'courses', 'filter', 'search'));
    }

    public function create()
    {
        return view('quiz.bank.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'course_id' => 'nullable|exists:courses,id',
            'visibility' => 'required|boolean',
        ]);
        QuestionBank::create([
            'name' => $request->name,
            'description' => $request->description,
            'course_id' => $request->course_id,
            'created_by' => auth()->id(),
            'visibility' => $request->visibility,
        ]);
        return redirect()->route('questionbank.index')->with('success', 'Question Bank created successfully.');
    }

    public function reorder(Request $request, QuestionBank $questionBank)
    {
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
        $questionBank->load('questions');
        $user = User::findOrFail(Auth::user()->id);
        $courses = $user->courses()->with('users')->get();

        return view('quiz.bank.show', compact('questionBank', 'courses'));
    }

    public function update($id, Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'course_id' => 'nullable|exists:courses,id',
            'visibility' => 'required|boolean',
        ]);

        $questionBank = QuestionBank::findOrFail($id);

        $questionBank->update([
            'name' => $request->name,
            'description' => $request->description,
            'course_id' => $request->course_id,
            'visibility' => $request->visibility,
        ]);

        return redirect()->route('questionbank.index')->with('success', 'Question Bank updated successfully.');
    }

    public function destroy($id)
    {
        $questionBank = QuestionBank::findOrFail($id);
        $questionBank->delete();
        return redirect()->route('questionbank.index')->with('success', 'Question Bank deleted successfully.');
    }
}
