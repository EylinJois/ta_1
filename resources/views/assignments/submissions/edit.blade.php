@extends('layouts.app')

@section('title', 'Grade Submission')

@section('page-title', 'Grade Submission')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/course.css') }}">
<link rel="stylesheet" href="{{ asset('css/qbank.css') }}">
@endpush

@section('content')

<div class="mb-4">
    <a href="{{ route('assignments.show', $assignment) }}" class="btn btn-secondary-custom">
        <i class="bi bi-arrow-left"></i>
    </a>
</div>

<div class="row g-4">

    <div class="col-lg-7">

        <div class="table-card mb-4">
            <h5 class="mb-3">{{ $submission->user->name }}</h5>
            <p class="text-muted mb-0">{{ $submission->assignment->name }}</p>
        </div>

        @if(in_array($submission->assignment->submission_type, ['text', 'both']))
        <div class="table-card mb-4">
            <h6 class="mb-3"><i class="bi bi-file-text"></i> Written Answer</h6>
            @if($submission->submitted_text)
            <div class="html-page">
                {!! $submission->submitted_text !!}
            </div>
            @else
            <p class="text-muted mb-0">No written answer submitted.</p>
            @endif
        </div>
        @endif

        @if(in_array($submission->assignment->submission_type, ['file', 'both']))
        <div class="table-card mb-4">
            <h6 class="mb-3"><i class="bi bi-paperclip"></i> Attached Files</h6>
            @if($submission->mediaAssets->isNotEmpty())
            <ul class="list-unstyled mb-0">
                @foreach($submission->mediaAssets as $file)
                <li class="d-flex align-items-center justify-content-between border rounded-3 px-3 py-2 mb-2">
                    <span><i class="bi bi-file-earmark-check text-success me-1"></i> {{ $file->original_name }}</span>
                    <a href="{{ route('media.download', $file) }}" class="btn btn-sm btn-secondary-custom">
                        <i class="bi bi-download"></i>
                    </a>
                </li>
                @endforeach
            </ul>
            @else
            <p class="text-muted mb-0">No files attached.</p>
            @endif
        </div>
        @endif

    </div>

    <div class="col-lg-5">

        <div class="table-card">

            <h5 class="mb-4"><i class="bi bi-clipboard-check"></i> Grade</h5>

            <form action="{{ route('submissions.grade.update', $submission) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-4">
                    <label class="form-label">Score</label>
                    <input type="number" step="0.01" min="0" name="score" class="form-control" required
                        value="{{ old('score', $submission->score) }}">
                </div>

                <div class="mb-4">
                    <label class="form-label">Feedback</label>
                    <textarea name="feedback" class="form-control" rows="8"
                        placeholder="Notes for the student...">{{ old('feedback', $submission->feedback) }}</textarea>
                </div>

                <button type="submit" class="btn btn-theme w-100">
                    <i class="bi bi-check-circle"></i>
                    {{ $submission->status === 'graded' ? 'Update Grade' : 'Submit Grade' }}
                </button>

            </form>

        </div>

    </div>

</div>

@endsection