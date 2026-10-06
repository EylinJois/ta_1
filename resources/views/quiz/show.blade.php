@extends('layouts.app')

@section('title', $quiz->name)

@section('page-title', 'Quiz')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/qbank.css') }}">
@endpush

@section('content')

<div class="mb-4 d-flex justify-content-between align-items-center">

    <a href="{{ route('sections.show', $quiz->section) }}" class="btn btn-icon-plain">
        <i class="bi bi-arrow-left"></i>
    </a>

    @if($isManager)
    <a href="{{ route('quizzes.attempts.index', $quiz) }}" class="btn btn-warning text-white">
        <i class="bi bi-clipboard-data"></i>
        View All Attempts
    </a>
    @endif

</div>

<div class="question-bank-banner mb-4">

    <div>

        <span class="badge bg-light text-dark mb-3">
            Quiz
            @if($isManager && !$quiz->visibility)
            <span class="text-danger">— Hidden from students</span>
            @endif
        </span>

        <h2>{{ $quiz->name }}</h2>

        <p class="mb-3">
            {{ $quiz->description }}
        </p>

        <div class="assignment-meta">

            <div class="assignment-meta-item">
                <span>Duration {{ $quiz->duration ? '(hh:mm)' : '' }}</span>
                <strong>
                    @if($quiz->duration)
                        {{ gmdate('H:i', $quiz->duration) }}
                    @else
                        <i class="bi bi-infinity"></i>
                    @endif
                </strong>
            </div>

            <div class="assignment-meta-item">
                <span>{{ Str::plural('Attempt', $quiz->max_attempt ?? 2) }} Allowed</span>
                <strong>
                    @if($quiz->max_attempt)
                        {{ $quiz->max_attempt }}
                    @else
                        <i class="bi bi-infinity"></i>
                    @endif
                </strong>
            </div>

            <div class="assignment-meta-item">
                <span>Pass Score</span>
                <strong>
                    @if($quiz->pass_score)
                        {{ rtrim(rtrim(number_format($quiz->pass_score, 2), '0'), '.') }}%
                    @else
                        —
                    @endif
                </strong>
            </div>

            <div class="assignment-meta-item">
                <span>Your Best Score</span>
                <strong>
                    @if(!is_null($bestScore))
                        {{ rtrim(rtrim(number_format($bestScore, 2), '0'), '.') }}%
                    @else
                        —
                    @endif
                </strong>
            </div>

        </div>

    </div>

</div>


<div class="table-card mb-4">
    <div class="text-center py-4">
        @if(!$isOpen)
        <p class="text-muted mb-3">
            <i class="bi bi-lock-fill"></i>
            @if($notYetOpen)
                This quiz is not available yet. It opens on
                {{ $quiz->opened_at }}.
            @else
                This quiz is closed. The due date was
                {{ $quiz->due_at }}.
            @endif
        </p>
        <button type="button" class="btn btn-secondary-custom btn-lg px-5" disabled>
            <i class="bi bi-slash-circle"></i>
            Unable to Attempt
        </button>

        @elseif($inProgressAttempt)
        <p class="text-muted mb-3">
            You have an attempt in progress, started
            {{ $inProgressAttempt->started_at->diffForHumans() }}.
        </p>
        <form action="{{ route('quizzes.attempts.store', $quiz) }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-theme btn-lg px-5">
                <i class="bi bi-arrow-right-circle"></i>
                Resume Attempt
            </button>
        </form>

        @elseif($canAttempt)
        <p class="text-muted mb-3">
            @if(!is_null($attemptsRemaining))
            You have {{ $attemptsRemaining }} {{ Str::plural('attempt', $attemptsRemaining) }} remaining.
            @else
            You have unlimited attempts for this quiz.
            @endif
        </p>
        <form action="{{ route('quizzes.attempts.store', $quiz) }}" method="POST" class="d-inline"
            onsubmit="return confirm('Start this quiz now? Make sure you\'re ready before beginning.')">
            @csrf
            <button type="submit" class="btn btn-theme btn-lg px-5">
                <i class="bi bi-play-circle"></i>
                Start Attempt
            </button>
        </form>

        @else
        <p class="text-muted mb-3">
            <i class="bi bi-lock-fill"></i>
            You have used all {{ $quiz->max_attempt }} of your allowed attempts.
        </p>
        <button type="button" class="btn btn-secondary-custom btn-lg px-5" disabled>
            No Attempts Remaining
        </button>
        @endif

    </div>
</div>

@if($attempts->isNotEmpty())

<div class="table-card">

    <h5 class="mb-3">
        <i class="bi bi-clock-history"></i>
        Your Attempts
    </h5>

    <div class="table-responsive">
        <table class="table table-hover align-middle text-center">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Status</th>
                    <th>Score</th>
                    <th>Started</th>
                    <th>Submitted</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($attempts as $attempt)
                <tr>
                    <td>{{ $attempt->attempt_no }}</td>
                    <td>
                        @switch($attempt->status)
                        @case('in_progress')
                        <span class="badge bg-warning text-dark">In Progress</span>
                        @break
                        @case('submitted')
                        <span class="badge bg-info text-dark">Submitted</span>
                        @break
                        @case('graded')
                        <span class="badge bg-success">Graded</span>
                        @break
                        @endswitch
                    </td>
                    <td>
                        @if(!is_null($attempt->score) && $attempt->status == 'graded')
                        {{ rtrim(rtrim(number_format($attempt->score, 2), '0'), '.') }}%
                            @if($quiz->pass_score)
                                @if($attempt->score >= $quiz->pass_score)
                                <span class="badge bg-success ms-1">Passed</span>
                                @else
                                <span class="badge bg-danger ms-1">Not Passed</span>
                                @endif
                            @endif
                        @else
                        —
                        @endif
                    </td>
                    <td>{{ $attempt->started_at->format('d M Y, H:i') }}</td>
                    <td>{{ $attempt->submitted_at ? $attempt->submitted_at->format('d M Y, H:i') : '—' }}</td>
                    <td>
                        <a href="{{ route('attempts.review', $attempt) }}"
                            class="btn edit-btn">
                            <i class="bi bi-eye-fill"></i>
                            Review Attempt
                        </a>
                    </td>
                </tr>
                    @endforeach
            </tbody>
        </table>
    </div>

</div>

@endif

@endsection