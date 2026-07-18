<div class="mb-4">
    <label class="form-label">Assignment Name</label>
    <input type="text" name="name" class="form-control" maxlength="255" required
        value="{{ old('name', $assignment->name ?? '') }}"
        placeholder="e.g. Chapter 3 Essay">
</div>

<div class="mb-4">
    <label class="form-label">Description (optional)</label>
    <textarea name="description" class="form-control" rows="6"
        placeholder="Explain what students need to do...">{{ old('description', $assignment->description ?? '') }}</textarea>
</div>

<div class="row">

    <div class="col-md-6 mb-4">
        <label class="form-label">Submission Type</label>
        <select name="submission_type" id="submissionType" class="form-select" required>
            <option value="text" @selected(old('submission_type', $assignment->submission_type ?? 'text') === 'text')>Text Only</option>
            <option value="file" @selected(old('submission_type', $assignment->submission_type ?? '') === 'file')>File Only</option>
            <option value="both" @selected(old('submission_type', $assignment->submission_type ?? '') === 'both')>Text + File</option>
        </select>
    </div>

    <div class="col-md-6 mb-4">
        <label class="form-label">Visibility</label>
        <select name="visibility" class="form-select">
            <option value="1" @selected(old('visibility', $assignment->visibility ?? 1) == 1)>Visible</option>
            <option value="0" @selected(old('visibility', $assignment->visibility ?? 1) == 0)>Hidden</option>
        </select>
    </div>

</div>

<div class="row">

    <div class="col-md-6 mb-4">
        <label class="form-label">
            Opens At
            <small class="text-muted">(optional)</small>
        </label>
        <input type="datetime-local" name="opened_at" class="form-control"
            value="{{ old('opened_at', isset($assignment) && $assignment->opened_at ? $assignment->opened_at->format('Y-m-d\TH:i') : '') }}">
    </div>

    <div class="col-md-6 mb-4">
        <label class="form-label">
            Due At
            <small class="text-muted">(optional)</small>
        </label>
        <input type="datetime-local" name="due_at" class="form-control"
            value="{{ old('due_at', isset($assignment) && $assignment->due_at ? $assignment->due_at->format('Y-m-d\TH:i') : '') }}">
    </div>

</div>

<div id="fileConfigSection" class="d-none">

    <label class="form-label">Accepted File Types</label>

    <div class="file-type-checkboxes mb-3">
        @php
        $existingMime = old('accepted_mime_manual') ? [] : ($assignment->accepted_mime ?? []);
        $knownExtensions = ['pdf' => 'PDF', 'doc' => 'DOC', 'docx' => 'DOCX', 'ppt' => 'PPT', 'pptx' => 'PPTX', 'jpg' => 'JPG', 'png' => 'PNG', 'zip' => 'ZIP'];
        @endphp
        @foreach($knownExtensions as $ext => $label)
        <div class="form-check form-check-inline">
            <input
                type="checkbox"
                class="form-check-input mime-checkbox"
                value="{{ $ext }}"
                id="mime{{ $ext }}"
                {{ in_array($ext, $existingMime) ? 'checked' : '' }}>
            <label class="form-check-label" for="mime{{ $ext }}">{{ $label }}</label>
        </div>
        @endforeach
    </div>

    <div class="mb-4">
        <label class="form-label">
            Other Extensions
            <small class="text-muted">(comma-separated, optional — e.g. mp4, csv)</small>
        </label>
        @php
        $customMime = collect($existingMime)->diff(array_keys($knownExtensions))->implode(', ');
        @endphp
        <input type="text" class="form-control" id="mimeCustom" placeholder="e.g. mp4, csv" value="{{ $customMime }}">
        <input type="hidden" name="accepted_mime_json" id="acceptedMimeJson" value="[]">
    </div>

    <div class="mb-4">
        <label class="form-label">
            Max File Size
            <small class="text-muted">(MB)</small>
        </label>
        <input type="number" name="max_size" class="form-control" min="1" max="1024"
            value="{{ old('max_size', $assignment->max_size ?? '') }}" placeholder="e.g. 20">
    </div>

</div>

<div class="row">

    <div class="col-md-6 mb-3">
        <div class="form-check form-switch">
            <input type="hidden" name="edit_submission" value="0">
            <input
                type="checkbox"
                class="form-check-input"
                id="editSubmission"
                name="edit_submission"
                value="1"
                {{ old('edit_submission', $assignment->edit_submission ?? false) ? 'checked' : '' }}>
            <label class="form-check-label" for="editSubmission">
                Allow Editing After Submission
            </label>
        </div>
    </div>

    <div class="col-md-6 mb-3">
        <div class="form-check form-switch">
            <input type="hidden" name="allow_late_submission" value="0">
            <input
                type="checkbox"
                class="form-check-input"
                id="allowLate"
                name="allow_late_submission"
                value="1"
                {{ old('allow_late_submission', $assignment->allow_late_submission ?? false) ? 'checked' : '' }}>
            <label class="form-check-label" for="allowLate">
                Allow Late Submission
            </label>
        </div>
    </div>

</div>