@extends('layouts.app')

@section('title', 'Attempts - ' . $quiz->name)

@section('page-title', 'Quiz Attempts')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/qbank.css') }}">
@endpush

@section('content')

<div class="mb-4">
    <a href="{{ route('quizzes.show', $quiz) }}" class="btn btn-secondary-custom">
        <i class="bi bi-arrow-left"></i>
    </a>
</div>

<div class="question-bank-banner mb-4">

    <div>
        <span class="badge bg-light text-dark mb-3">
            Attempts
        </span>

        <h2>{{ $quiz->name }}</h2>

        <p class="mb-0">
            All student attempts for this quiz.
        </p>
    </div>

</div>

{{-- Summary stats --}}
<div class="row g-3 mb-4">

    <div class="col-md-3">
        <div class="stat-card">
            <h3>{{ $totalAttempts }}</h3>
            <span>Total Attempts</span>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-card">
            <h3>{{ $uniqueStudents }}</h3>
            <span>Students Attempted</span>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-card">
            <h3>
                @if(!is_null($averageScore))
                {{ rtrim(rtrim(number_format($averageScore, 2), '0'), '.') }}
                @else
                —
                @endif
            </h3>
            <span>Average Score</span>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-card">
            <h3>
                @if(!is_null($passCount))
                {{ $passCount }}
                @else
                —
                @endif
            </h3>
            <span>Passed</span>
        </div>
    </div>

</div>

{{-- Filters --}}
<form method="GET" action="{{ route('quizzes.attempts.index', $quiz) }}" class="qbank-toolbar mb-4">

    <div class="qbank-search">
        <i class="bi bi-search"></i>
        <input
            type="text"
            name="search"
            value="{{ $search }}"
            class="form-control"
            placeholder="Search by student name or email..."
            onchange="this.form.submit()">
    </div>

    <div class="qbank-filter-pills">

        <a href="{{ route('quizzes.attempts.index', array_filter(['quiz' => $quiz->id, 'search' => $search])) }}"
            class="filter-pill {{ $status === 'all' ? 'active' : '' }}">
            All
        </a>

        <a href="{{ route('quizzes.attempts.index', array_filter(['quiz' => $quiz->id, 'search' => $search, 'status' => 'in_progress'])) }}"
            class="filter-pill {{ $status === 'in_progress' ? 'active' : '' }}">
            In Progress
        </a>

        <a href="{{ route('quizzes.attempts.index', array_filter(['quiz' => $quiz->id, 'search' => $search, 'status' => 'submitted'])) }}"
            class="filter-pill {{ $status === 'submitted' ? 'active' : '' }}">
            Submitted
        </a>

        <a href="{{ route('quizzes.attempts.index', array_filter(['quiz' => $quiz->id, 'search' => $search, 'status' => 'graded'])) }}"
            class="filter-pill {{ $status === 'graded' ? 'active' : '' }}">
            Graded
        </a>

    </div>

</form>

<div class="table-card">

    @if($attempts->isNotEmpty())

    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Attempt #</th>
                    <th>Status</th>
                    <th>Score</th>
                    <th>Duration</th>
                    <th>Started</th>
                    <th>Submitted</th>
                </tr>
            </thead>
            <tbody>
                @foreach($attempts as $attempt)
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $attempt->user->name }}</div>
                        <div class="text-muted small">{{ $attempt->user->email }}</div>
                    </td>

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
                        @if(!is_null($attempt->score))
                        {{ rtrim(rtrim(number_format($attempt->score, 2), '0'), '.') }}
                        @if($quiz->pass_score)
                            @if($attempt->score >= $quiz->pass_score)
                            <span class="badge bg-success ms-1">Pass</span>
                            @else
                            <span class="badge bg-danger ms-1">Fail</span>
                            @endif
                        @endif
                        @else
                        —
                        @endif
                    </td>

                    <td>
                        @if(!is_null($attempt->duration_seconds))
                        {{ gmdate('H:i:s', $attempt->duration_seconds) }}
                        @else
                        <span class="text-muted">In progress</span>
                        @endif
                    </td>

                    <td>{{ $attempt->started_at->format('d M Y, H:i') }}</td>
                    <td>{{ $attempt->submitted_at ? $attempt->submitted_at->format('d M Y, H:i') : '—' }}</td>

                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @else

    <div class="empty-state">
        <i class="bi bi-clock-history"></i>
        <h5>No Attempts Found</h5>
        <p class="text-muted">
            @if($search || $status !== 'all')
            No attempts match your search or filter.
            @else
            No students have attempted this quiz yet.
            @endif
        </p>
    </div>

    @endif

</div>

@endsection