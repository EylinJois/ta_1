<?php

namespace App\Http\Controllers;

use App\Models\CourseUserRole;
use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Auth;

class CourseController extends Controller
{
    public function index()
    {
        $user=User::findOrFail(Auth::user()->id);
        $courses = $user->courses()->with('users')->get();
        return view('courses.index', compact('courses'));
    }

    public function create()
    {
        return view('courses.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'img' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg',
            'description' => 'nullable|string',
        ]);

        $course = new Course();
        $course->name = $request->input('name');
        $course->description = $request->input('description');

        if ($request->hasFile('img')) {
            $imagePath = $request->file('img')->store('courses', 'public');
            $course->img = $imagePath;
        }

        $course->save();

        CourseUserRole::create([
            'course_id' => $course->id,
            'user_id' => auth()->user()->id,
            'role' => 'admin',
        ]);
        // $course->users()->attach(auth()->user()->id, ['role' => 'admin']);

        return redirect()->route('courses.index')->with('success', 'Course created successfully.');
    }

    public function destroy($id)
    {
        $course = Course::findOrFail($id);

        if ($course->img) {
            Storage::delete('public/' . $course->img);
        }

        $course->delete();

        return redirect()->route('courses.index')->with('success', 'Course deleted successfully.');
    }

    public function edit($id)
    {
        $course = Course::findOrFail($id);
        return view('courses.edit', compact('course'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'img' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg',
            'description' => 'nullable|string',
        ]);

        $course = Course::findOrFail($id);
        $course->name = $request->input('name');
        $course->description = $request->input('description');

        if ($request->hasFile('img')) {
            if ($course->img) {
                Storage::delete('public/' . $course->img);
            }
            $imagePath = $request->file('img')->store('courses', 'public');
            $course->img = $imagePath;
        }

        $course->save();

        return redirect()->route('courses.index')->with('success', 'Course updated successfully.');
    }

    public function show($id)
    {
        $course = Course::with([
            'sections' => function ($query) {
                $query->orderBy('order_idx', 'asc');
            }
        ])->findOrFail($id);

        $users = User::whereNotIn(
            'id',
            $course->users->pluck('id')
        )->get();

        $sections = $course->sections()
            ->with('materials')
            ->orderBy('order_idx', 'asc')
            ->get();


        return view('courses.show', compact(
            'course',
            'users',
            'sections'
        ));
    }

    public function indexParticipants($id)
    {
        $course = Course::with('users')->findOrFail($id);
        $users = User::all();
        return view('courses.participants.index', compact('course', 'users'));
    }

    public function addParticipant(Request $request, $id)
    {
        $request->validate([
            'user_id' => 'required',
            'role' => 'required',
        ]);

        $course = Course::findOrFail($id);

        $course->users()->attach(
            $request->user_id,
            [
                'role' => $request->role
            ]
        );

        return back()->with(
            'success',
            'Participant added.'
        );
    }

    public function updateParticipantRole(Request $request, $id, $participantId)
    {
        $request->validate([
            'role' => 'required',
        ]);

        $course = Course::findOrFail($id);

        $course->users()->updateExistingPivot(
            $participantId,
            ['role' => $request->role]
        );

        return back()->with(
            'success',
            'Participant role updated.'
        );
    }

    public function removeParticipant($id, $participantId)
    {
        $course = Course::findOrFail($id);

        $course->users()->detach($participantId);

        return back()->with(
            'success',
            'Participant removed.'
        );
    }
}
