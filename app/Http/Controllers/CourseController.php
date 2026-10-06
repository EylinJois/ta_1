<?php

namespace App\Http\Controllers;

use App\Models\ClassUserRole;
use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use App\Models\Clas;
use Illuminate\Validation\Rule;
use Auth;

class CourseController extends Controller
{
    public function index()
    {
        $classes = auth()->user()
            ->classes()
            ->with(['course' => fn($query) => $query->withCount('sections')])
            ->get()
            ->filter(fn($class) => $class->course);

        return view('courses.index', compact('classes'));
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

        $class = Clas::create([
            'course_id' => $course->id,
            'name' => 'Default Class',
        ]);
        $class->users()->attach(auth()->id(), ['role' => 'admin']);

        return redirect()->route('courses.index')->with('success', 'Course created successfully.');
    }

    public function destroy($id)
    {
        $course = Course::findOrFail($id);
        $this->authorizeCourseAdmin($course);

        if ($course->img) {
            Storage::delete('public/' . $course->img);
        }

        $course->delete();

        return redirect()->route('courses.index')->with('success', 'Course deleted successfully.');
    }

    public function edit($id)
    {
        $course = Course::findOrFail($id);
        $this->authorizeCourseAdmin($course);
        return view('courses.edit', compact('course'));
    }

    public function update(Request $request, $id)
    {
        $course = Course::findOrFail($id);
        $this->authorizeCourseAdmin($course);

        $request->validate([
            'name' => 'required|string|max:255',
            'img' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg',
            'description' => 'nullable|string',
        ]);

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
        $course = Course::findOrFail($id);
        $user   = auth()->user();


        $classes = $course->classes()
            ->whereHas('users', fn($query) => $query->where('users.id', $user->id))
            ->with('users')
            ->orderBy('name')
            ->get();

        abort_if($classes->isEmpty(), 403);

        $users = User::whereNotIn('id', $classes->flatMap->users->pluck('id')->unique())->get();

        $sections = $course->sections()
            ->with('materials')
            ->orderBy('order_idx', 'asc')
            ->get();

        return view('courses.show', compact('course', 'classes', 'users', 'sections'));
    }

    public function indexParticipants($id)
    {
        $course  = Course::findOrFail($id);
        $classes = $course->classes()->with('users')->orderBy('name')->get();
        $users   = User::orderBy('name')->get();

        $authUser  = auth()->user();
        $isAdmin   = $authUser->isAdminInCourse($course->id);
        $canManage = $authUser->isManagerInCourse($course->id);
        $colspan   = $canManage ? 5 : 4;

        // distinct participants: a user in several classes is counted once
        $total = $classes->flatMap->users->pluck('id')->unique()->count();

        return view('courses.participants.index', compact(
            'course',
            'classes',
            'users',
            'isAdmin',
            'canManage',
            'colspan',
            'total',
        ));
    }

    public function addParticipant(Request $request, $id)
    {
        $course = Course::findOrFail($id);
        $this->authorizeCourseAdmin($course);

        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'user_id'  => 'required|exists:users,id',
            'role'     => ['required', Rule::in(['admin', 'teacher', 'student'])],
        ]);

        $class = $course->classes()->findOrFail($validated['class_id']);

        // syncWithoutDetaching prevents duplicate rows for the same user in a class
        $class->users()->syncWithoutDetaching([
            $validated['user_id'] => ['role' => $validated['role']],
        ]);

        return back()->with('success', 'Participant added.');
    }

    public function updateParticipantRole(Request $request, $id, $participantId)
    {
        $course = Course::findOrFail($id);
        $this->authorizeCourseAdmin($course);

        $validated = $request->validate([
            'old_class_id' => 'required|exists:classes,id',
            'class_id'     => 'required|exists:classes,id',
            'role'         => ['required', Rule::in(['admin', 'teacher', 'student'])],
        ]);
        $oldClass = $course->classes()->findOrFail($validated['old_class_id']);
        $newClass = $course->classes()->findOrFail($validated['class_id']);

        $participant = ClassUserRole::where('class_id', $oldClass->id)
            ->where('user_id', $participantId);

        abort_unless($participant->exists(), 404);

        // Moving into a class the user is already in would create a duplicate row
        if ($oldClass->id !== $newClass->id) {
            $alreadyInTarget = ClassUserRole::where('class_id', $newClass->id)
                ->where('user_id', $participantId)
                ->exists();

            if ($alreadyInTarget) {
                return back()->withErrors([
                    'class_id' => 'This user is already a participant of the selected class.',
                ]);
            }
        }

        $participant->update([
            'class_id' => $newClass->id,
            'role'     => $validated['role'],
        ]);

        return back()->with('success', 'Participant updated.');
    }

    public function removeParticipant(Request $request, $id, $participantId)
    {
        $course = Course::findOrFail($id);
        $this->authorizeCourseAdmin($course);
        $validated = $request->validate(['class_id' => 'required|exists:classes,id']);

        $class = $course->classes()->findOrFail($validated['class_id']);
        $class->users()->detach($participantId);

        return back()->with('success', 'Participant removed.');
    }

    public function storeClass(Request $request, $id)
    {
        $course = Course::findOrFail($id);
        $this->authorizeCourseAdmin($course);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                // class names must be unique within the course
                Rule::unique('classes', 'name')
                    ->where('course_id', $course->id)
                    ->whereNull('deleted_at'), // Clas uses SoftDeletes
            ],
        ]);

        Clas::create([
            'course_id' => $course->id,
            'name'      => $validated['name'],
        ]);

        return back()->with('success', 'Class added.');
    }

    public function updateClass(Request $request, $id, $classId)
    {
        $course = Course::findOrFail($id);
        abort_unless(auth()->user()->isAdmin($classId), 403);

        $class = $course->classes()->findOrFail($classId);

        // named error bag so these errors don't trigger the "Add Class" modal
        $validated = $request->validateWithBag('renameClass', [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('classes', 'name')
                    ->where('course_id', $course->id)
                    ->whereNull('deleted_at')
                    ->ignore($class->id),
            ],
        ]);

        $class->update(['name' => $validated['name']]);

        return back()->with('success', 'Class renamed.');
    }

    public function destroyClass($id, $classId)
    {
        $course = Course::findOrFail($id);
        abort_unless(auth()->user()->isAdmin($classId), 403);

        $class = $course->classes()->findOrFail($classId);

        // Remove every class_user_role row for this class (members leave the class
        // and therefore the course), then delete the class itself.
        $class->users()->detach();
        $class->delete();

        return back()->with('success', 'Class deleted and its participants were removed.');
    }

    private function authorizeCourseAdmin(Course $course): void
    {
        abort_unless(auth()->user()->isAdminInCourse($course->id), 403);
    }
}
