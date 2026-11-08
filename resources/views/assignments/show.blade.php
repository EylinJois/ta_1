@extends('layouts.app')

@section('title', $assignment->name)

@section('page-title', 'Assignment')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/qbank.css') }}">
@endpush

@section('content')

<div class="mb-4">
    <a href="{{ route('sections.show', $assignment->section) }}" class="btn btn-secondary-custom" style="background: transparent;">
        <i class="bi bi-arrow-left"></i>
    </a>
</div>

<div class="question-bank-banner mb-4">
    <div>
        <span class="badge bg-light text-dark mb-3">
            Assignment
            @can('update', $assignment)
            @if(!$assignment->visibility)
            <span class="text-danger">— Hidden from students</span>
            @endif
            @endcan
        </span>
        <h2>{{ $assignment->name }}</h2>
        <p class="mb-3">{!! nl2br(e($assignment->description)) !!}</p>

        <div class="assignment-meta">

            <div class="assignment-meta-item">
                <span>Submission type</span>
                <strong>
                    @if($assignment->submission_type === 'both')
                    Written or File
                    @else
                    {{ ucfirst($assignment->submission_type) }}
                    @endif
                </strong>
            </div>

            @can('viewAny', [\App\Models\Submission::class, $assignment])
            <div class="assignment-meta-item">
                <span>Total submissions</span>
                <strong>{{ $submissions->count() }}</strong>
            </div>

            <div class="assignment-meta-item">
                <span>Graded</span>
                <strong>{{ $submissions->where('status', 'graded')->count() }} / {{ $submissions->count() }}</strong>
            </div>
            @else
            <div class="assignment-meta-item">
                <span>Score</span>
                <strong>{{ $submission?->score ?? '-' }}</strong>
            </div>

            <div class="assignment-meta-item">
                <span>Submission status</span>
                <strong>
                    @if($submission)
                    @switch($submission->status)
                    @case('draft') Draft @break
                    @case('submitted') Submitted @break
                    @case('late') Submitted Late @break
                    @case('graded') Graded @break
                    @endswitch
                    @else
                    Not Started
                    @endif
                </strong>
            </div>
            @endcan

            <div class="assignment-meta-item">
                <span>Due on</span>
                <strong>{{ $assignment->due_at ? $assignment->due_at->format('d M Y, H:i') : '—' }}</strong>
            </div>

        </div>
    </div>
</div>

@can('viewAny', [\App\Models\Submission::class, $assignment])

{{-- ===================== MANAGER VIEW: SUBMISSIONS LIST ===================== --}}

<div class="table-card">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">
            <i class="bi bi-list-check"></i>
            Submissions
        </h5>
    </div>

    @if($classes->isNotEmpty())
    <div class="class-filter-row mb-3">
        <span class="fw-semibold text-muted">Class:</span>
        <div class="qbank-filter-pills">
            <a href="{{ route('assignments.show', $assignment) }}"
                class="filter-pill {{ $classId === 'all' ? 'active' : '' }}">
                All classes
            </a>
            @foreach($classes as $class)
            <a href="{{ route('assignments.show', ['assignment' => $assignment, 'class_id' => $class->id]) }}"
                class="filter-pill {{ (string) $classId === (string) $class->id ? 'active' : '' }}">
                {{ $class->name }}
            </a>
            @endforeach
        </div>
    </div>
    @endif

    @if($submissions->isNotEmpty())

    <div class="table-responsive">
        <table class="table table-hover align-middle text-center assignment-submissions-table">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Status</th>
                    <th>Score</th>
                    <th>Submitted</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($submissions as $sub)
                <tr>
                    <td data-label="Student">
                        <div class="assignment-student-details">
                            <div class="fw-semibold">{{ $sub->user->name }}</div>
                            <div class="text-muted small">{{ $sub->user->email }}</div>
                        </div>
                    </td>

                    <td data-label="Status">
                        @switch($sub->status)
                        @case('draft')
                        <span class="badge bg-secondary">Draft</span>
                        @break
                        @case('submitted')
                        <span class="badge bg-info text-dark">Submitted</span>
                        @break
                        @case('late')
                        <span class="badge bg-warning text-dark">Late</span>
                        @break
                        @case('graded')
                        <span class="badge bg-success">Graded</span>
                        @break
                        @endswitch
                    </td>

                    <td data-label="Score">
                        {{ !is_null($sub->score) ? rtrim(rtrim(number_format($sub->score, 2), '0'), '.') : '—' }}
                    </td>

                    <td data-label="Submitted">
                        {{ $sub->submitted_at ? $sub->submitted_at->format('d M Y, H:i') : '—' }}
                    </td>

                    <td data-label="Actions" class="assignment-submission-actions">

                        @if($sub->status !== 'draft')
                        <a href="{{ route('submissions.edit', $sub) }}" class="btn edit-btn">
                            <i class="bi bi-pencil-fill"></i>
                            Grade
                        </a>
                        @endif

                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @else

    <div class="empty-state">
        <i class="bi bi-inbox"></i>
        <h5>No Submissions Yet</h5>
        <p class="text-muted">No students have submitted this assignment yet.</p>
    </div>

    @endcan

</div>

@else

{{-- ===================== STUDENT VIEW: SUBMISSION FORM ===================== --}}

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
<div class="table-card student-submission-card mb-4">
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
        <h5 class="submission-header">
            <i class="bi bi-pencil-square"></i>
            Your Submission
        </h5>
        @if(!$canEdit)
        <span class="badge bg-secondary"><i class="bi bi-lock-fill"></i> Locked</span>
        @else
        @if($submission)
        <span class="qbank-badge">Submitted on {{ $submission->submitted_at->format('d M Y, H:i') }}</span>
        @endif
        @endif
    </div>

    <form id="submissionForm">

        @if(in_array($assignment->submission_type, ['text', 'both']))
        <div class="mb-4">
            <label class="form-label">Your Answer</label>
            <textarea class="form-control tinymce-editor" id="submittedText" rows="10" {{ $canEdit ? '': 'disabled'}}>{!! $submission->submitted_text ?? '' !!}</textarea>
        </div>
        @endif

        @if(in_array($assignment->submission_type, ['file', 'both']))
        <div class="uploaded-file">
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
        <div class="d-flex justify-content-end submit-button-wrapper">
            <div id="submitMessage" class="alert mb-0 py-2 d-none"></div>
            <button type="button" class="btn btn-theme" id="submitAssignmentBtn"
                @if(!$canSubmitLate) disabled @endif>
                <i class="bi bi-check-circle"></i>
                Submit Assignment
            </button>
        </div>
        @endif

    </form>

</div>

@endif

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {

        const isManager = @json(auth()->user()->can('viewAny', [\App\Models\Submission::class, $assignment]));
        if (isManager) {
            return;
        }

        const assignmentId = {{$assignment -> id}};
        const deleteFileUrl = @json(route('submissions.file.delete', ['assignment' => $assignment, 'mediaAsset' => '__MEDIA_ASSET__']));
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        const canEdit = @json($canEdit);

        /*=================================================
            TINYMCE
        =================================================*/

        function initTinyMCE() {
            const textarea = document.getElementById('submittedText');
            if (!textarea) {
                return;
            }
            if (typeof tinymce === 'undefined') {
                console.error('TinyMCE failed to load — falling back to plain textarea.');
                textarea.style.display = '';
                return;
            }
            if (tinymce.get('submittedText')) {
                tinymce.get('submittedText').remove();
            }
            tinymce.init({
                selector: '#submittedText',
                height: 300,
                width: '100%',
                resize: false,
                menubar: true,
                plugins: 'lists link',
                toolbar: 'undo redo | blocks | bold italic underline | bullist numlist | link',
                readonly: !canEdit, // prevents editing from the very first render
                setup: function(editor) {
                    editor.on('init', function() {
                        if (!canEdit) {
                            editor.mode.set('readonly'); // belt-and-suspenders — some TinyMCE 6 builds need this called post-init too
                        }
                    });
                },
            }).catch(function(err) {
                console.error('TinyMCE init failed:', err);
                textarea.style.display = '';
            });
        }

        initTinyMCE();

        function getSubmittedText() {
            const editor = tinymce.get('submittedText');
            if (editor) {
                return editor.getContent();
            }
            const textarea = document.getElementById('submittedText');
            return textarea ? textarea.value : '';
        }

        function showMessage(text, type) {
            const box = document.getElementById('submitMessage');
            box.textContent = text;
            box.className = 'alert alert-' + type + ' mb-0 py-2';
        }

        function submitForm() {
            const formData = new FormData();
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
                .then(async function(r) {
                    const contentType = r.headers.get('content-type') || '';

                    if (r.ok) {
                        const body = await r.json();
                        showMessage(body.message || 'Assignment submitted.', 'success');
                        setTimeout(() => window.location.reload(), 1500);
                        return;
                    }

                    if (contentType.includes('application/json')) {
                        const body = await r.json();
                        const message = body.errors ?
                            Object.values(body.errors).flat().join(' ') :
                            (body.message || 'Failed to submit.');
                        throw new Error(message);
                    }

                    console.error('Non-JSON error response:', await r.text());
                    throw new Error(`Something went wrong (HTTP ${r.status}).`);
                })
                .catch(function(err) {
                    showMessage(err.message, 'danger');
                });
        }

        const submitBtn = document.getElementById('submitAssignmentBtn');
        if (submitBtn) {
            submitBtn.addEventListener('click', function() {
                if (confirm('Submit this assignment? You may not be able to edit it afterward.')) {
                    submitForm();
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

            fetch(deleteFileUrl.replace('__MEDIA_ASSET__', encodeURIComponent(btn.dataset.mediaId)), {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
            }).then(function(r) {
                if (!r.ok) {
                    throw new Error('Failed to remove attached file.');
                }
                btn.closest('li').remove();
            }).catch(function(err) {
                console.error(err);
                alert(err.message);
            });
        });

    });
</script>
@endpush