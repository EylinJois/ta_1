<?php

namespace App\Http\Controllers;

use App\Models\ClassUserRole;
use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Clas;
use App\Models\MediaAsset;
use Illuminate\Validation\Rule;
use RuntimeException;

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
        $this->authorize('create', Course::class);

        return view('courses.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', Course::class);

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'img'         => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // KB
            'description' => 'nullable|string',
        ]);

        $disk = 'public';
        $storedPath = null;

        try {
            DB::transaction(function () use ($request, $validated, $disk, &$storedPath) {
                $course = new Course();
                $course->name = $validated['name'];
                $course->description = $validated['description'] ?? null;

                if ($request->hasFile('img')) {
                    $file = $request->file('img');

                    $originalName = $file->getClientOriginalName();
                    $mime = $file->getMimeType();
                    $size = $file->getSize();

                    $storedPath = $file->store('courses', $disk);

                    $media = MediaAsset::create([
                        'storage_disk'  => $disk,
                        'file_path'     => $storedPath,
                        'original_name' => $originalName,
                        'mime'          => $mime,
                        'size'          => $size,
                        'uploaded_by'   => auth()->id(),
                    ]);

                    $course->img_media_id = $media->id;
                }

                $course->save();

                $class = Clas::create([
                    'course_id' => $course->id,
                    'name'      => 'Default Class',
                ]);

                $class->users()->attach(auth()->id(), ['role' => 'admin']);
            });
        } catch (\Throwable $e) {
            if ($storedPath) {
                Storage::disk($disk)->delete($storedPath);
            }
            throw $e;
        }

        return redirect()->route('courses.index')
            ->with('success', 'Course created successfully.');
    }

    public function destroy($id)
    {
        $course = Course::findOrFail($id);
        $this->authorize('delete', $course);

        if ($course->imgMedia) {
            Storage::delete('public/' . $course->imgMedia);
        }

        $course->delete();

        return redirect()->route('courses.index')->with('success', 'Course deleted successfully.');
    }

    public function edit($id)
    {
        $course = Course::findOrFail($id);
        $this->authorize('update', $course);
        return view('courses.edit', compact('course'));
    }

    public function update(Request $request, $id)
    {
        $course = Course::findOrFail($id);
        $this->authorize('update', $course);

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'img'         => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'description' => 'nullable|string',
        ]);

        $disk = 'public';
        $newPath = null;
        $oldMedia = null;

        try {
            DB::transaction(function () use ($request, $validated, $course, $disk, &$newPath, &$oldMedia) {
                $course->fill([
                    'name'        => $validated['name'],
                    'description' => $validated['description'] ?? null,
                ]);

                if ($request->hasFile('img')) {
                    $file = $request->file('img');

                    $oldMedia = $course->imgMedia;

                    $newPath = $file->store('courses', $disk);

                    $media = MediaAsset::create([
                        'storage_disk'  => $disk,
                        'file_path'     => $newPath,
                        'original_name' => $file->getClientOriginalName(),
                        'mime'          => $file->getMimeType(),
                        'size'          => $file->getSize(),
                        'uploaded_by'   => auth()->id(),
                    ]);

                    $course->imgMedia()->associate($media);
                }

                $course->save();
            });
        } catch (\Throwable $e) {
            if ($newPath) {
                Storage::disk($disk)->delete($newPath);
            }
            throw $e;
        }

        if ($oldMedia) {
            Storage::disk($oldMedia->storage_disk)->delete($oldMedia->file_path);
            $oldMedia->delete();
        }

        return redirect()->route('courses.index')
            ->with('success', 'Course updated successfully.');
    }

    public function show($id)
    {
        $course = Course::findOrFail($id);
        $this->authorize('view', $course);
        $user   = auth()->user();

        $classes = $course->classes()
            ->whereHas('users', fn($query) => $query->where('users.id', $user->id))
            ->with('users')
            ->orderBy('name')
            ->get();

        $users = User::whereNotIn('id', $classes->flatMap->users->pluck('id')->unique())->get();

        $url = 'courses.show';

          $sections = $course->sections()
            ->with('materials')
            ->orderBy('order_idx', 'asc')
            ->where(function ($query) use ($user, $course) {
                if (!$user->isManagerInCourse($course->id)) {
                    $query->where('visibility', true);
                }
            })
            ->get();

        return view('courses.show', compact('course', 'classes', 'users', 'sections', 'url'));
    }

    public function indexParticipants($id)
    {
        $course  = Course::findOrFail($id);
        $this->authorize('view', $course);
        $classes = $course->classes()->with('users')->orderBy('name')->get();
        $users   = User::orderBy('name')->get();

        $authUser  = auth()->user();
        $canManage = $authUser->isManagerInCourse($course->id);
        $colspan   = $canManage ? 5 : 4;

        // distinct participants: a user in several classes is counted once
        $total = $classes->flatMap->users->pluck('id')->unique()->count();

        return view('courses.participants.index', compact(
            'course',
            'classes',
            'users',
            'canManage',
            'colspan',
            'total',
        ));
    }

    public function addParticipant(Request $request, $id)
    {
        $course = Course::findOrFail($id);
        $this->authorize('manageParticipants', $course);

        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'user_id'  => 'required|exists:users,id',
            'role'     => ['required', Rule::in(['admin', 'teacher', 'student'])],
        ]);

        $class = $course->classes()->findOrFail($validated['class_id']);

        $alreadyInCourse = $course->classes()
            ->whereHas('users', fn($q) => $q->where('users.id', $validated['user_id']))
            ->exists();

        if ($alreadyInCourse) {
            return back()
                ->withErrors(['user_id' => 'This user is already a participant of a class in this course.'])
                ->withInput();
        }

        $class->users()->syncWithoutDetaching([
            $validated['user_id'] => ['role' => $validated['role']],
        ]);

        return back()->with('success', 'Participant added.');
    }

    public function importParticipants(Request $request, $id)
    {
        $course = Course::findOrFail($id);
        $this->authorize('manageParticipants', $course);

        $validated = $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $handle = fopen($validated['csv_file']->getRealPath(), 'r');
        if ($handle === false) {
            throw new RuntimeException('Unable to read the uploaded CSV file.');
        }

        $records = [];
        $errors = [];

        try {
            $header = fgetcsv($handle, 0, ',', '"', '\\');
            if ($header === false) {
                return back()->withErrors(['csv_import' => 'The CSV file is empty.']);
            }

            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]);
            $headers = array_map(fn ($column) => strtolower(trim((string) $column)), $header);
            $requiredHeaders = ['email', 'role', 'class'];
            $headerIndexes = [];

            foreach ($requiredHeaders as $requiredHeader) {
                $matches = array_keys($headers, $requiredHeader, true);
                if (\count($matches) !== 1) {
                    return back()->withErrors([
                        'csv_import' => 'The CSV must contain exactly one email, role, and class column.',
                    ]);
                }
                $headerIndexes[$requiredHeader] = $matches[0];
            }

            $seenEmails = [];
            $rowNumber = 1;
            while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
                $rowNumber++;
                if (\count(array_filter($row, fn ($value) => trim((string) $value) !== '')) === 0) {
                    continue;
                }

                $row = array_pad($row, \count($headers), '');
                $email = strtolower(trim((string) $row[$headerIndexes['email']]));
                $role = strtolower(trim((string) $row[$headerIndexes['role']]));
                $className = trim((string) $row[$headerIndexes['class']]);
                $rowErrors = [];

                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $rowErrors[] = 'enter a valid email address';
                } elseif (isset($seenEmails[$email])) {
                    $rowErrors[] = 'email is duplicated in the CSV';
                } else {
                    $seenEmails[$email] = true;
                }

                if (!\in_array($role, ['admin', 'teacher', 'student'], true)) {
                    $rowErrors[] = 'role must be admin, teacher, or student';
                }

                if ($className === '') {
                    $rowErrors[] = 'class is required';
                }

                if ($rowErrors) {
                    $errors[] = 'Row ' . $rowNumber . ': ' . implode('; ', $rowErrors) . '.';
                    continue;
                }

                $records[] = [
                    'row' => $rowNumber,
                    'email' => $email,
                    'role' => $role,
                    'class_name' => $className,
                ];
            }
        } finally {
            fclose($handle);
        }

        if (!$records && !$errors) {
            $errors[] = 'The CSV contains no participant rows.';
        }

        $courseClasses = $course->classes()->get();
        $classesByName = $courseClasses
            ->groupBy(fn (Clas $class) => strtolower(trim($class->name)));
        $emails = array_values(array_unique(array_column($records, 'email')));
        $usersByEmail = User::query()
            ->whereIn(DB::raw('LOWER(email)'), $emails)
            ->get()
            ->keyBy(fn (User $user) => strtolower($user->email));

        $existingUserIds = ClassUserRole::query()
            ->whereIn('class_id', $courseClasses->pluck('id'))
            ->whereIn('user_id', $usersByEmail->pluck('id'))
            ->pluck('user_id')
            ->all();
        $existingUserIds = array_fill_keys($existingUserIds, true);
        $importRows = [];

        foreach ($records as $record) {
            $rowErrors = [];
            $user = $usersByEmail->get($record['email']);
            $matchingClasses = $classesByName->get(strtolower($record['class_name']), collect());
            $class = $matchingClasses->count() === 1 ? $matchingClasses->first() : null;

            if (!$user) {
                $rowErrors[] = 'no existing account was found for ' . $record['email'];
            }

            if (!$class) {
                $rowErrors[] = $matchingClasses->isEmpty()
                    ? 'class "' . $record['class_name'] . '" does not exist in this course'
                    : 'class "' . $record['class_name'] . '" is ambiguous; use a unique class name';
            }

            if ($user && isset($existingUserIds[$user->id])) {
                $rowErrors[] = $record['email'] . ' is already enrolled in this course';
            }

            if ($rowErrors) {
                $errors[] = 'Row ' . $record['row'] . ': ' . implode('; ', $rowErrors) . '.';
                continue;
            }

            $importRows[] = [
                'class' => $class,
                'user' => $user,
                'role' => $record['role'],
            ];
        }

        if ($errors) {
            return back()->withErrors(['csv_import' => $errors]);
        }

        DB::transaction(function () use ($importRows) {
            foreach ($importRows as $importRow) {
                $importRow['class']->users()->attach($importRow['user']->id, [
                    'role' => $importRow['role'],
                ]);
            }
        });

        return back()->with('success', \count($importRows) . ' participant(s) imported successfully.');
    }

    public function updateParticipantRole(Request $request, $id, $participantId)
    {
        $course = Course::findOrFail($id);
        $this->authorize('manageParticipants', $course);

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
        $this->authorize('manageParticipants', $course);
        $validated = $request->validate(['class_id' => 'required|exists:classes,id']);

        $class = $course->classes()->findOrFail($validated['class_id']);
        $class->users()->detach($participantId);

        return back()->with('success', 'Participant removed.');
    }

    public function storeClass(Request $request, $id)
    {
        $course = Course::findOrFail($id);
        $this->authorize('manageClasses', $course);

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
        $class = $course->classes()->findOrFail($classId);
        $this->authorize('manageClasses', $course);

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
        $class = $course->classes()->findOrFail($classId);
        $this->authorize('manageClasses', $course);

        // Remove every class_user_role row for this class (members leave the class
        // and therefore the course), then delete the class itself.
        $class->users()->detach();
        $class->delete();

        return back()->with('success', 'Class deleted and its participants were removed.');
    }
}
