<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\MediaAsset;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SubmissionController extends Controller
{
    private function editableGuard(Assignment $assignment, ?Submission $submission)
    {
        if ($submission && $submission->status === 'graded') {
            abort(422, 'This submission has already been graded and can no longer be edited.');
        }

        if ($submission && $submission->status !== 'draft' && !$assignment->edit_submission) {
            abort(422, 'This assignment does not allow editing after submission.');
        }

        $isPastDue = $assignment->due_at && now()->gt($assignment->due_at);
        if ($isPastDue && !$assignment->allow_late_submission) {
            abort(422, 'The due date has passed and this assignment does not allow late submissions.');
        }
    }

    public function store(Request $request, Assignment $assignment)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'submitted_text' => 'nullable|string',
            'action' => 'required|in:draft,submit',
        ]);

        $existingSubmission = Submission::where('assignment_id', $assignment->id)
            ->where('user_id', $user->id)
            ->first();

        $isPastDue = $assignment->due_at && now()->gt($assignment->due_at);

        // No submission exists yet, and the window is genuinely closed — block
        // starting one at all, regardless of draft vs. submit action.
        if (!$existingSubmission && $isPastDue && !$assignment->allow_late_submission) {
            return response()->json([
                'message' => 'The due date has passed and this assignment can no longer be started.',
            ], 422);
        }

        $submission = $existingSubmission ?? new Submission([
            'assignment_id' => $assignment->id,
            'user_id' => $user->id,
        ]);

        $this->editableGuard($assignment, $existingSubmission);

        if ($validated['action'] === 'submit' && $isPastDue && !$assignment->allow_late_submission) {
            return response()->json([
                'message' => 'The due date has passed and late submissions are not allowed.',
            ], 422);
        }

        $submission->submitted_text = $validated['submitted_text'] ?? $submission->submitted_text;

        if ($validated['action'] === 'submit') {

            $hasText = filled($submission->submitted_text);
            $hasFiles = $submission->exists && $submission->mediaAssets()->exists();

            $error = match ($assignment->submission_type) {
                'text' => !$hasText ? 'Please write your answer before submitting.' : null,
                'file' => !$hasFiles ? 'Please attach a file before submitting.' : null,
                'both' => (!$hasText && !$hasFiles) ? 'Please provide a written answer or attach a file before submitting.' : null,
                default => null,
            };

            if ($error) {
                return response()->json(['message' => $error], 422);
            }
        }

        if ($validated['action'] === 'draft') {
            $submission->status = 'draft';
        } else {
            $submission->status = $isPastDue ? 'late' : 'submitted';
            $submission->submitted_at = now();
        }

        $submission->save();

        return response()->json([
            'status' => 'ok',
            'message' => $validated['action'] === 'draft' ? 'Draft saved.' : 'Assignment submitted.',
        ]);
    }

    public function uploadFile(Request $request, Assignment $assignment)
    {
        $user = auth()->user();

        $existingSubmission = Submission::where('assignment_id', $assignment->id)
            ->where('user_id', $user->id)
            ->first();

        $isPastDue = $assignment->due_at && now()->gt($assignment->due_at);

        if (!$existingSubmission && $isPastDue && !$assignment->allow_late_submission) {
            return response()->json([
                'message' => 'The due date has passed and this assignment can no longer be started.',
            ], 422);
        }

        $submission = $existingSubmission ?? Submission::create([
            'assignment_id' => $assignment->id,
            'user_id' => $user->id,
            'status' => 'draft',
            'submitted_at' => now(),
        ]);

        $this->editableGuard($assignment, $existingSubmission);

        $validated = $request->validate([
            'file' => 'required|file|max:' . (($assignment->max_size ?: 100) * 1024),
        ]);

        if ($assignment->accepted_mime) {
            $ext = strtolower($request->file('file')->getClientOriginalExtension());
            if (!in_array($ext, $assignment->accepted_mime)) {
                return response()->json(['message' => 'File type not allowed for this assignment.'], 422);
            }
        }

        $path = $request->file('file')->store('submissions', 'public');

        $mediaAsset = MediaAsset::create([
            'file_path' => $path,
            'original_name' => $request->file('file')->getClientOriginalName(),
            'uploaded_by' => $user->id,
        ]);

        $submission->mediaAssets()->attach($mediaAsset->id);

        return response()->json([
            'status' => 'ok',
            'media_asset_id' => $mediaAsset->id,
            'file_name' => $mediaAsset->original_name,
        ]);
    }

    public function deleteFile(Assignment $assignment, MediaAsset $mediaAsset)
    {
        $user = auth()->user();

        $submission = Submission::where('assignment_id', $assignment->id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $this->editableGuard($assignment, $submission);

        $submission->mediaAssets()->detach($mediaAsset->id);
        Storage::disk('public')->delete($mediaAsset->file_path);
        $mediaAsset->delete();

        return response()->json(['status' => 'ok']);
    }

    private function authorizeManage($section)
    {
        $course = $section->course;
        abort_unless(
            auth()->user()->isAdmin($course->id) || auth()->user()->isTeacher($course->id),
            403
        );
    }

    public function index(Assignment $assignment)
    {
        $this->authorizeManage($assignment->section);

        $submissions = Submission::with('user')
            ->where('assignment_id', $assignment->id)
            ->orderByRaw("FIELD(status, 'submitted', 'late', 'graded', 'draft')")
            ->orderByDesc('submitted_at')
            ->get();

        return view('assignments.submissions.index', compact('assignment', 'submissions'));
    }

    public function show(Submission $submission)
    {
        $this->authorizeManage($submission->assignment->section);

        $submission->load(['user', 'mediaAssets', 'assignment', 'grader']);

        return view('assignments.submissions.show', compact('submission'));
    }

    public function edit(Submission $submission)
    {
        $this->authorizeManage($submission->assignment->section);

        $submission->load(['user', 'mediaAssets', 'assignment']);

        return view('assignments.submissions.edit', compact('submission'));
    }

    public function gradeUpdate(Request $request, Submission $submission)
    {
        $this->authorizeManage($submission->assignment->section);

        $validated = $request->validate([
            'score' => 'required|numeric|min:0',
            'feedback' => 'nullable|string',
        ]);

        $submission->update([
            'score' => $validated['score'],
            'feedback' => $validated['feedback'] ?? null,
            'status' => 'graded',
            'graded_by' => auth()->id(),
            'graded_at' => now(),
        ]);

        return redirect()
            ->route('assignments.submissions.index', $submission->assignment_id)
            ->with('success', 'Submission graded.');
    }
}
