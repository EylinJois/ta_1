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

    $submission = Submission::firstOrNew([
        'assignment_id' => $assignment->id,
        'user_id' => $user->id,
    ]);

    $this->editableGuard($assignment, $submission->exists ? $submission : null);

    $isPastDue = $assignment->due_at && now()->gt($assignment->due_at);
    if ($validated['action'] === 'submit' && $isPastDue && !$assignment->allow_late_submission) {
        return back()->withErrors(['submitted_text' => 'The due date has passed and late submissions are not allowed.']);
    }

    $submission->submitted_text = $validated['submitted_text'] ?? $submission->submitted_text;

    if ($validated['action'] === 'submit') {

        $hasText = filled($submission->submitted_text);
        $hasFiles = $submission->exists && $submission->mediaAssets()->exists();

        $error = match ($assignment->submission_type) {

            'text' => !$hasText
                ? 'Please write your answer before submitting.'
                : null,

            'file' => !$hasFiles
                ? 'Please attach a file before submitting.'
                : null,

            // 'both': either is acceptable, but at least one is required —
            // this is the option-for-students behavior.
            'both' => (!$hasText && !$hasFiles)
                ? 'Please provide a written answer or attach a file before submitting.'
                : null,

            default => null,
        };

        if ($error) {
            return back()->withErrors(['submitted_text' => $error])->withInput();
        }
    }

    if ($validated['action'] === 'draft') {
        $submission->status = 'draft';
    } else {
        $submission->status = $isPastDue ? 'late' : 'submitted';
        $submission->submitted_at = now();
    }

    $submission->save();

    return back()->with('success', $validated['action'] === 'draft' ? 'Draft saved.' : 'Assignment submitted.');
}

    public function uploadFile(Request $request, Assignment $assignment)
    {
        $user = auth()->user();

        $submission = Submission::firstOrCreate(
            ['assignment_id' => $assignment->id, 'user_id' => $user->id],
            ['status' => 'draft', 'submitted_at' => now()]
        );

        $this->editableGuard($assignment, $submission);

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
}