<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Assignment;
use App\Models\Submission;
use App\Models\Section;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Activity;

class AssignmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }


    /**
     * Display the specified resource.
     */
    public function show(Request $request, Assignment $assignment)
    {
        $user = auth()->user();
        $course = $assignment->section->course;
        $this->authorize('view', $assignment);
        $isManager = $user->isManagerInCourse($course->id);

        $isOpen = !$assignment->opened_at || now()->gte($assignment->opened_at);
        abort_unless($isManager || $isOpen, 404, 'This assignment is not open yet.');

        $isPastDue = $assignment->due_at && now()->gt($assignment->due_at);

        $submission = Submission::with('mediaAssets')
            ->where('assignment_id', $assignment->id)
            ->where('user_id', $user->id)
            ->first();

        $canEdit = $this->computeCanEdit($assignment, $submission, $isPastDue);

        if ($submission && $submission->status === 'draft' && $isPastDue && !$canEdit) {
            $submission->update([
                'status' => 'late',
                'submitted_at' => now(),
            ]);
            $submission->refresh();
        }

        $canSubmitLate = !$isPastDue || $assignment->allow_late_submission;

        $classes = $isManager
            ? $course->classes()->orderBy('name')->get()
            : collect();
        $classId = $request->input('class_id', 'all');
        if ($classId !== 'all') {
            $selectedClass = $classes->first(
                fn ($class) => is_scalar($classId) && (string) $class->id === (string) $classId
            );
            $classId = $selectedClass?->id ?? 'all';
        }

        $submissions = $isManager
            ? Submission::with('user')
            ->where('assignment_id', $assignment->id)
            ->when($classId !== 'all', function ($q) use ($classId) {
                $q->whereHas('user.classes', function ($classQuery) use ($classId) {
                    $classQuery->where('classes.id', $classId)
                        ->where('class_user_role.role', 'student');
                });
            })->orderByRaw("FIELD(status, 'submitted', 'late', 'graded', 'draft')")
            ->orderByDesc('submitted_at')->get(): collect();

        return view('assignments.show', compact(
            'assignment','submission','isOpen', 'isPastDue','canEdit',
            'canSubmitLate','isManager','submissions','classes','classId'
        ));
    }

    /**
     * Shared editability rule — used here and mirrored in
     * SubmissionController::editableGuard() so both stay in sync.
     */
    private function computeCanEdit(Assignment $assignment, ?Submission $submission, bool $isPastDue): bool
    {
        $canEdit = true;

        if ($submission) {
            if ($submission->status === 'graded') {
                $canEdit = false;
            } elseif ($submission->status !== 'draft' && !$assignment->edit_submission) {
                $canEdit = false;
            }
        }

        if ($canEdit && $isPastDue && !$assignment->allow_late_submission) {
            $canEdit = false;
        }

        return $canEdit;
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function create(Section $section)
    {
        $this->authorize('create', [Assignment::class, $section]);

        return view('assignments.create', compact('section'));
    }

    public function store(Request $request, Section $section)
    {
        $this->authorize('create', [Assignment::class, $section]);

        $validated = $this->validateAssignment($request);

        $assignment = Assignment::create(array_merge($validated, [
            'section_id' => $section->id,
        ]));

        if ($assignment->opened_at) {
            $activity = Activity::firstOrNew([
                'source_type' => 'assignment',
                'source_id' => $assignment->id,
                'name' => 'Assignment Opened',
            ]);

            $isNew = !$activity->exists;

            $activity->fill([
                'description' => $assignment->name . ' is opened on ' . $assignment->opened_at->format('d M Y, H:i'),
                'starts_at' => $assignment->opened_at,
                'type' => 'Assignment',
            ])->save();

            if ($isNew) {
                foreach ($assignment->section->course->classes as $class) {
                    $activity->participants()->syncWithoutDetaching($class->users()->pluck('users.id')->toArray());
                }
            }
        }

        if ($assignment->due_at) {
            $activity = Activity::firstOrNew([
                'source_type' => 'assignment',
                'source_id' => $assignment->id,
                'name' => 'Assignment Due',
            ]);

            $isNew = !$activity->exists;

            $activity->fill([
                'description' => $assignment->name . ' is due on ' . $assignment->due_at->format('d M Y, H:i'),
                'starts_at' => $assignment->due_at,
                'type' => 'Assignment',
            ])->save();

            if ($isNew) {
                foreach ($assignment->section->course->classes as $class) {
                    $activity->participants()->syncWithoutDetaching($class->users()->pluck('users.id')->toArray());
                }
            }
        }

        return redirect()
            ->route('sections.show', $section)
            ->with('success', 'Assignment created.');
    }

    public function edit(Assignment $assignment)
    {
        $this->authorize('update', $assignment);

        return view('assignments.edit', compact('assignment'));
    }

    public function update(Request $request, Assignment $assignment)
    {
        $this->authorize('update', $assignment);

        $validated = $this->validateAssignment($request);

        $assignment->update($validated);

        if ($assignment->opened_at) {
            $activity = Activity::firstOrNew([
                'source_type' => 'assignment',
                'source_id' => $assignment->id,
                'name' => 'Assignment Opened',
            ]);

            $isNew = !$activity->exists;

            $activity->fill([
                'description' => $assignment->name . ' is opened on ' . $assignment->opened_at->format('d M Y, H:i'),
                'starts_at' => $assignment->opened_at,
                'type' => 'Assignment',
            ])->save();

            if ($isNew) {
                foreach ($assignment->section->course->classes as $class) {
                    $activity->participants()->syncWithoutDetaching($class->users()->pluck('users.id')->toArray());
                }
            }
        }

        if ($assignment->due_at) {
            $activity = Activity::firstOrNew([
                'source_type' => 'assignment',
                'source_id' => $assignment->id,
                'name' => 'Assignment Due',
            ]);

            $isNew = !$activity->exists;

            $activity->fill([
                'description' => $assignment->name . ' is due on ' . $assignment->due_at->format('d M Y, H:i'),
                'starts_at' => $assignment->due_at,
                'type' => 'Assignment',
            ])->save();

            if ($isNew) {
                foreach ($assignment->section->course->classes as $class) {
                    $activity->participants()->syncWithoutDetaching($class->users()->pluck('users.id')->toArray());
                }
            }
        }

        return redirect()
            ->route('sections.show', $assignment->section)
            ->with('success', 'Assignment updated.');
    }

    private function validateAssignment(Request $request): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'submission_type' => 'required|in:text,file,both',
            'visibility' => 'required|boolean',
            'due_at' => 'nullable|date',
            'opened_at' => 'nullable|date',
            'accepted_mime_json' => 'nullable|string',
            'max_size' => 'nullable|integer|min:1|max:1024',
            'edit_submission' => 'required|boolean',
            'allow_late_submission' => 'required|boolean',
        ]);

        if (in_array($validated['submission_type'], ['file', 'both'])) {
            $allowed = json_decode($request->input('accepted_mime_json', '[]'), true) ?: [];
            $validated['accepted_mime'] = array_values(array_unique($allowed));
        } else {
            $validated['accepted_mime'] = null;
            $validated['max_size'] = null;
        }

        unset($validated['accepted_mime_json']);

        return $validated;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $assignment = Assignment::findOrFail($id);
        $this->authorize('delete', $assignment);

        DB::transaction(function () use ($assignment) {
            $submissions = Submission::with('mediaAssets')
                ->where('assignment_id', $assignment->id)
                ->get();

            foreach ($submissions as $submission) {
                foreach ($submission->mediaAssets as $mediaAsset) {
                    Storage::disk('public')->delete($mediaAsset->file_path);
                    $mediaAsset->delete(); 
                }
                $submission->delete();
            }
            $assignment->delete();
        });
        return back()->with('success', 'Assignment and all related submissions deleted.');
    }
}
