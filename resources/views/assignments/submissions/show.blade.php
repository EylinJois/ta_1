@extends('layouts.app')

@section('title', 'Submission - ' . $submission->user->name)

@section('page-title', 'Submission')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/qbank.css') }}">
@endpush

@section('content')

<div class="mb-4">
    <a href="{{ route('submissions.index', $submission->assignment_id) }}" class="btn btn-secondary-custom">
        <i class="bi bi-arrow-left"></i>
    </a>
</div>

<div class="question-bank-banner mb-4">
    <div>
        <span class="badge bg-light text-dark mb-3">{{ $submission->assignment->name }}</span>
        <h2>{{ $submission->user->name }}</h2>
        <p class="mb-0">{{ $submission->user->email }}</p>
    </div>
</div>

<div class="row g-3 mb-4">

    <div class="col-md-3">
        <div class="stat-card">
            <h3>
                @switch($submission->status)
                @case('draft') Draft @break
                @case('submitted') Submitted @break
                @case('late') Late @break
                @case('graded') Graded @break
                @endswitch
            </h3>
            <span>Status</span>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-card">
            <h3>{{ $submission->submitted_at ? $submission->submitted_at->format('d M Y') : '—' }}</h3>
            <span>Submitted</span>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-card">
            <h3>{{ !is_null($submission->score) ? rtrim(rtrim(number_format($submission->score, 2), '0'), '.') : '—' }}</h3>
            <span>Score</span>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-card">
            <h3>{{ $submission->grader?->name ?? '—' }}</h3>
            <span>Graded By</span>
        </div>
    </div>

</div>

@if(in_array($submission->assignment->submission_type, ['text', 'both']))
<div class="table-card mb-4">
    <h5 class="mb-3"><i class="bi bi-file-text"></i> Written Answer</h5>
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
    <h5 class="mb-3"><i class="bi bi-paperclip"></i> Attached Files</h5>
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

@if($submission->status === 'graded' && $submission->feedback)
<div class="table-card mb-4">
    <h5 class="mb-3"><i class="bi bi-chat-left-text"></i> Feedback</h5>
    <p class="mb-0">{{ $submission->feedback }}</p>
</div>
@endif

<div class="d-flex justify-content-end">
    <a href="{{ route('submissions.edit', $submission) }}" class="btn btn-theme">
        <i class="bi bi-pencil-fill"></i>
        {{ $submission->status === 'graded' ? 'Edit Grade' : 'Grade Submission' }}
    </a>
</div>

@endsection