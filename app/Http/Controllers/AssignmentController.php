<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Assignment;
use App\Models\Submission;
use App\Models\Section;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
    public function show(Assignment $assignment)
    {
        $user = auth()->user();
        $course = $assignment->section->course;
        $isManager = $user->isManagerInCourse($course->id);

        $isVisible = $isManager || $user->id === $assignment->created_by || $assignment->visibility;
        abort_unless($isVisible, 404);

        $isOpen = !$assignment->opened_at || now()->gte($assignment->opened_at);
        abort_unless($isManager || $isOpen, 404, 'This assignment is not open yet.');

        $isPastDue = $assignment->due_at && now()->gt($assignment->due_at);

        $submission = Submission::with('mediaAssets')
            ->where('assignment_id', $assignment->id)
            ->where('user_id', $user->id)
            ->first();

        $canEdit = $this->computeCanEdit($assignment, $submission, $isPastDue);

        // Auto-finalize: a draft left sitting once the window has genuinely
        // closed (past due AND no longer editable) gets submitted as-is,
        // rather than staying an invisible draft forever. Only applies to
        // students who actually started something — see store() for the
        // separate rule blocking brand-new submissions after this point.
        if ($submission && $submission->status === 'draft' && $isPastDue && !$canEdit) {
            $submission->update([
                'status' => 'late',
                'submitted_at' => now(),
            ]);
            $submission->refresh();
        }

        $canSubmitLate = !$isPastDue || $assignment->allow_late_submission;

        $submissions = $isManager
            ? Submission::with('user')
            ->where('assignment_id', $assignment->id)
            ->orderByRaw("FIELD(status, 'submitted', 'late', 'graded', 'draft')")
            ->orderByDesc('submitted_at')
            ->get()
            : collect();

        return view('assignments.show', compact(
            'assignment',
            'submission',
            'isOpen',
            'isPastDue',
            'canEdit',
            'canSubmitLate',
            'isManager',
            'submissions'
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
    private function authorizeManage($section)
    {
        $course = $section->course;

        abort_if(is_null($course), 500, 'Section is not linked to a valid course.');

        abort_unless(
            auth()->user()->isManagerInCourse($course->id),
            403
        );
    }

    public function create(Section $section)
    {
        $this->authorizeManage($section);

        return view('assignments.create', compact('section'));
    }

    public function store(Request $request, Section $section)
    {
        $this->authorizeManage($section);

        $validated = $this->validateAssignment($request);

        $assignment = Assignment::create(array_merge($validated, [
            'section_id' => $section->id,
            'created_by' => auth()->id(),
        ]));

        return redirect()
            ->route('sections.show', $section)
            ->with('success', 'Assignment created.');
    }

    public function edit(Assignment $assignment)
    {
        $this->authorizeManage($assignment->section);

        return view('assignments.edit', compact('assignment'));
    }

    public function update(Request $request, Assignment $assignment)
    {
        $this->authorizeManage($assignment->section);

        $validated = $this->validateAssignment($request);

        $assignment->update($validated);

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
        $this->authorizeManage($assignment->section);

        DB::transaction(function () use ($assignment) {

            $submissions = Submission::with('mediaAssets')
                ->where('assignment_id', $assignment->id)
                ->get();

            foreach ($submissions as $submission) {

                // Delete each attached file off disk before removing the DB
                // rows, so nothing orphaned is left behind on storage.
                foreach ($submission->mediaAssets as $mediaAsset) {
                    Storage::disk('public')->delete($mediaAsset->file_path);
                    $mediaAsset->delete(); // also removes the submission_files pivot rows via cascade
                }

                $submission->delete();
            }

            $assignment->delete();
        });

        return back()->with('success', 'Assignment and all related submissions deleted.');
    }
}
