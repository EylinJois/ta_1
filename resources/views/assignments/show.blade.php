@extends('layouts.app')

@section('title', $assignment->name)

@section('page-title', 'Assignment')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/qbank.css') }}">
<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js" referrerpolicy="origin"></script>
@endpush

@section('content')

<div class="mb-4">
    <a href="{{ route('sections.show', $assignment->section) }}" class="btn btn-secondary-custom">
        <i class="bi bi-arrow-left"></i>
    </a>
</div>

<div class="question-bank-banner mb-4">

    <div>
        <span class="badge bg-light text-dark mb-3">
            Assignment
            @if($isManager && !$assignment->visibility)
            <span class="text-danger">— Hidden from students</span>
            @endif
        </span>

        <h2>{{ $assignment->name }}</h2>

        <p class="mb-0">{{ $assignment->description }}</p>
    </div>

</div>

<div class="row g-3 mb-4">

    <div class="col-md-3">
        <div class="stat-card">
            <span>Submission type:</span>
            @if($assignment->submission_type === 'both')
            <h4>Written or File</h4>
            @else
            <h4>{{ ucfirst($assignment->submission_type) }}</h4>
            @endif
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-card">
            <span>Due on:</span>
            <h4>{{ $assignment->due_at ? $assignment->due_at->format('d M Y, H:m') : '—' }}</h4>
        </div>
    </div>


    <div class="col-md-3">
        <div class="stat-card">
            <span>Submission status:</span></h3>
            <h4>
                @if($submission)
                @switch($submission->status)
                @case('draft') Draft @break
                @case('submitted') Submitted @break
                @case('late') Late @break
                @case('graded') Graded @break
                @endswitch
                @else
                Not Started
                @endif
            </h4>
        </div>
    </div>

</div>

@if($isPastDue && !$assignment->allow_late_submission && (!$submission || $submission->status === 'draft'))
<div class="alert alert-danger">
    <i class="bi bi-exclamation-triangle"></i>
    The due date has passed and late submissions aren't accepted for this assignment.
</div>
@elseif($isPastDue && $assignment->allow_late_submission && (!$submission || in_array($submission->status, ['draft'])))
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle"></i>
    The due date has passed, but late submissions are still accepted.
</div>
@endif

@if($submission && $submission->status === 'graded')

<div class="table-card mb-4">
    <h5 class="mb-3"><i class="bi bi-clipboard-check"></i> Grade</h5>
    <div class="d-flex align-items-center gap-3 mb-3">
        <h2 class="mb-0">{{ rtrim(rtrim(number_format($submission->score, 2), '0'), '.') }}</h2>
        <span class="text-muted">Score</span>
    </div>
    @if($submission->feedback)
    <div class="alert alert-light border">
        {{ $submission->feedback }}
    </div>
    @endif
</div>

@endif

<div class="table-card">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">
            <i class="bi bi-pencil-square"></i>
            Your Submission
        </h5>
        @if(!$canEdit)
        <span class="badge bg-secondary"><i class="bi bi-lock-fill"></i> Locked</span>
        @endif
    </div>

    <form id="submissionForm">

        @if(in_array($assignment->submission_type, ['text', 'both']))
        <div class="mb-4">
            <label class="form-label">Your Answer</label>
            <textarea class="form-control tinymce-editor" id="submittedText" rows="10" {{ !$canEdit ? 'disabled' : '' }}>{!! $submission->submitted_text ?? '' !!}</textarea>
        </div>
        @endif

        @if(in_array($assignment->submission_type, ['file', 'both']))
        <div class="mb-4">
            <label class="form-label">
                Attach Files
                @if($assignment->max_size)
                <small class="text-muted">(max {{ $assignment->max_size }}MB each)</small>
                @endif
            </label>

            @if($canEdit)
            <input type="file" class="form-control" id="submissionFileInput"
                @if($assignment->accepted_mime) accept="{{ collect($assignment->accepted_mime)->map(fn($e) => '.'.$e)->implode(',') }}" @endif>
            @endif

            <ul class="list-unstyled mt-3 mb-0" id="submissionFileList">
                @foreach($submission?->mediaAssets ?? [] as $file)
                <li class="d-flex align-items-center justify-content-between border rounded-3 px-3 py-2 mb-2" data-media-id="{{ $file->id }}">
                    <span><i class="bi bi-file-earmark-check text-success me-1"></i> {{ $file->original_name }}</span>
                    @if($canEdit)
                    <button type="button" class="btn btn-sm btn-outline-danger remove-submission-file-btn" data-media-id="{{ $file->id }}">
                        <i class="bi bi-trash"></i>
                    </button>
                    @endif
                </li>
                @endforeach
            </ul>
        </div>
        @endif

        @if($canEdit)
        <div class="d-flex gap-2 justify-content-end">
            <button type="button" class="btn btn-secondary-custom" id="saveDraftBtn">
                <i class="bi bi-save"></i>
                Save Draft
            </button>
            <button type="button" class="btn btn-theme" id="submitAssignmentBtn"
                @if(!$canSubmitLate) disabled @endif>
                <i class="bi bi-check-circle"></i>
                Submit Assignment
            </button>
        </div>
        @endif

    </form>

</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {

        const assignmentId = {{ $assignment->id }};
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        const canEdit = @json($canEdit);

        /*=================================================
    TINYMCE
=================================================*/

        function initTinyMCE() {

            if (!document.getElementById("submittedText")) {
                return;
            }

            if (tinymce.get("submittedText")) {
                tinymce.get("submittedText").remove();
            }

             tinymce.init({
                selector: "#pageContent",
                height: 500,
                menubar: true,
                plugins: "lists link fullscreen",
                toolbar: "undo redo | blocks | bold italic | code"
            });

        }

        initTinyMCE();

        function getSubmittedText() {

            const editor = tinymce.get("submittedText");

            if (editor) {
                return editor.getContent();
            }

            const textarea = document.getElementById("submittedText");
            return textarea ? textarea.value : "";

        }

       function submitForm(action) {
    const formData = new FormData();
    formData.append('action', action);
    if (document.getElementById('submittedText')) {
        formData.append('submitted_text', getSubmittedText());
    }

    fetch(`/assignments/${assignmentId}/submission`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
        },
        body: formData,
    })
        .then(async function (r) {
            const contentType = r.headers.get('content-type') || '';

            if (r.ok) {
                window.location.reload();
                return;
            }

            if (contentType.includes('application/json')) {
                const body = await r.json();
                const message = body.errors
                    ? Object.values(body.errors).flat().join(' ')
                    : (body.message || 'Failed to save.');
                throw new Error(message);
            }

            // Non-JSON error response (HTML error page, etc.) — surface
            // the status instead of trying to parse it as JSON.
            console.error('Non-JSON error response:', await r.text());
            throw new Error(`Something went wrong (HTTP ${r.status}). Check the console for details.`);
        })
        .catch(function (err) {
            alert(err.message);
        });
}

        const saveDraftBtn = document.getElementById('saveDraftBtn');
        if (saveDraftBtn) {
            saveDraftBtn.addEventListener('click', function() {
                submitForm('draft');
            });
        }

        const submitBtn = document.getElementById('submitAssignmentBtn');
        if (submitBtn) {
            submitBtn.addEventListener('click', function() {
                if (confirm('Submit this assignment? You may not be able to edit it afterward.')) {
                    submitForm('submit');
                }
            });
        }

        const fileInput = document.getElementById('submissionFileInput');
        if (fileInput) {
            fileInput.addEventListener('change', function() {
                const file = fileInput.files[0];
                if (!file) return;

                const formData = new FormData();
                formData.append('file', file);

                fetch(`/assignments/${assignmentId}/submission/file`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: formData,
                    })
                    .then(async function(r) {
                        const body = await r.json();
                        if (!r.ok) throw new Error(body.message || 'Upload failed');

                        const li = document.createElement('li');
                        li.className = 'd-flex align-items-center justify-content-between border rounded-3 px-3 py-2 mb-2';
                        li.dataset.mediaId = body.media_asset_id;
                        li.innerHTML =
                            '<span><i class="bi bi-file-earmark-check text-success me-1"></i> ' + body.file_name + '</span>' +
                            '<button type="button" class="btn btn-sm btn-outline-danger remove-submission-file-btn" data-media-id="' + body.media_asset_id + '">' +
                            '<i class="bi bi-trash"></i></button>';
                        document.getElementById('submissionFileList').appendChild(li);
                        fileInput.value = '';
                    })
                    .catch(function(err) {
                        alert(err.message);
                        fileInput.value = '';
                    });
            });
        }

        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.remove-submission-file-btn');
            if (!btn) return;

            fetch(`/assignments/${assignmentId}/submission/file/${btn.dataset.mediaId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
            }).then(function(r) {
                if (r.ok) btn.closest('li').remove();
            });
        });

    });
</script>
@endpush