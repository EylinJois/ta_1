@extends('layouts.app')

@section('title', 'Submissions - ' . $assignment->name)

@section('page-title', 'Submissions')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/qbank.css') }}">
@endpush

@section('content')

<div class="mb-4">
    <a href="{{ route('assignments.show', $assignment) }}" class="btn btn-secondary-custom">
        <i class="bi bi-arrow-left"></i>
    </a>
</div>

<div class="question-bank-banner mb-4">
    <div>
        <span class="badge bg-light text-dark mb-3">Submissions</span>
        <h2>{{ $assignment->name }}</h2>
        <p class="mb-0">
            {{ $submissions->count() }} {{ Str::plural('submission', $submissions->count()) }} total.
        </p>
    </div>
</div>

<div class="table-card">

    @if($submissions->isNotEmpty())

    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Status</th>
                    <th>Score</th>
                    <th>Submitted</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($submissions as $submission)
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $submission->user->name }}</div>
                        <div class="text-muted small">{{ $submission->user->email }}</div>
                    </td>

                    <td>
                        @switch($submission->status)
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

                    <td>
                        @if(!is_null($submission->score))
                        {{ rtrim(rtrim(number_format($submission->score, 2), '0'), '.') }}
                        @else
                        —
                        @endif
                    </td>

                    <td>
                        {{ $submission->submitted_at ? $submission->submitted_at->format('d M Y, H:i') : '—' }}
                    </td>

                    <td class="text-end">

                        <a href="{{ route('submissions.show', $submission) }}" class="btn btn-secondary btn-sm">
                            <i class="bi bi-eye"></i>
                        </a>

                        @if($submission->status !== 'draft')
                        <a href="{{ route('submissions.edit', $submission) }}" class="icon-action-btn edit-btn btn-sm">
                            <i class="bi bi-pencil-fill"></i>
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

    @endif

</div>

@endsection