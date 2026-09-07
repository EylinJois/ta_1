@extends('layouts.app')

@section('title', 'Progress')

@section('page-title', 'Progress')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/course.css') }}">
<link rel="stylesheet" href="{{ asset('css/qbank.css') }}">
<link rel="stylesheet" href="{{ asset('css/progress.css') }}">
@endpush

@section('content')

<div class="courses-header mb-4">
    <div>
        <h4 class="mb-1">My Progress</h4>
        <p class="text-muted mb-0">
            Select a course to view your progress.
        </p>
    </div>
</div>

<div class="row g-4 mb-5">

    @forelse($courses as $course)

    <div class="col-lg-4 col-md-6">

        <a
            href="{{ route('assignments.show', $course->id) }}"
            class="text-decoration-none text-dark">

            <div class="progress-course-card">

                <div class="progress-course-icon">

                    <i class="bi bi-mortarboard-fill"></i>

                </div>

                <h5>
                    {{ $course->name }}
                </h5>

                @if(!empty($course->description))

                <p>
                    {{ Str::limit($course->description, 100) }}
                </p>

                @else

                <p>
                    View your learning progress for this course.
                </p>

                @endif

            </div>

        </a>

    </div>

    @empty

    <div class="col-12">

        <div class="empty-state">

            <i class="bi bi-journal-x"></i>

            <h5>
                No Courses
            </h5>

            <p class="text-muted">
                You are not enrolled in any courses.
            </p>

        </div>

    </div>

    @endforelse

</div>


{{-- =========================
     PROGRESS HISTORY
========================= --}}

<div class="progress-history-card">

    <div class="progress-history-header">

        <h5>
            <i class="bi bi-clipboard-data me-2"></i>
            Progress History
        </h5>

    </div>


    @if($reports->isNotEmpty())

    <div class="table-responsive">

        <table class="table align-middle progress-table">

            <thead>

                <tr>
                    <th>Title</th>
                    <th>Period</th>
                    <th>Progress</th>
                    <th>Quiz Avg</th>
                    <th>Comment</th>
                    <th>Date</th>
                </tr>

            </thead>

            <tbody>

                @foreach($reports as $report)

                <tr>

                    <td class="fw-semibold">
                        {{ $report->title }}
                    </td>

                    <td>

                        @if($report->start_period || $report->end_period)

                        {{ $report->start_period?->format('d M Y') }}

                        –

                        {{ $report->end_period?->format('d M Y') }}

                        @else

                        —

                        @endif

                    </td>

                    <td>

                        <span class="progress-badge">
                            {{ $report->progress }}%
                        </span>

                    </td>

                    <td>

                        {{ !is_null($report->avg_quiz_score)
                                    ? rtrim(
                                        rtrim(
                                            number_format(
                                                $report->avg_quiz_score,
                                                2
                                            ),
                                            '0'
                                        ),
                                        '.'
                                    )
                                    : '—'
                                }}

                    </td>

                    <td>

                        {{ $report->comment ?: '—' }}

                    </td>

                    <td>

                        {{ $report->created_at->format('d M Y') }}

                    </td>

                </tr>

                @endforeach

            </tbody>

        </table>

    </div>

    @else

    <div class="empty-state">

        <i class="bi bi-clipboard-x"></i>

        <h5>
            No Progress Reports Yet
        </h5>

        <p class="text-muted">
            Your teacher hasn't added a progress report yet.
        </p>

    </div>

    @endif

</div>

@endsection