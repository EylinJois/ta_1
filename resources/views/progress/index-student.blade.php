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

    @foreach($groups as $courseId => $group)

    @php
        $course = $group['course'];
        $groupReports = $group['reports'];
        $latest = $groupReports->first();
    @endphp

    <div class="col-lg-4 col-md-6">

        <a href="{{ route('progress.course', ['studentId' => Auth::id(), 'courseId' => $courseId]) }}"
            class="text-decoration-none text-dark">

            <div class="progress-course-card">

                <div class="progress-course-icon">
                    <i class="bi bi-mortarboard-fill"></i>
                </div>

                <h5>{{ $latest?->origin_name ?: ($course?->name ?? 'Unknown course') }}</h5>

                @if(!empty($course?->description))
                <p>{{ Str::limit($course->description, 100) }}</p>
                @else
                <p>View your learning progress for this course.</p>
                @endif

                <div class="small text-muted">
                    @if($groupReports->isNotEmpty())
                    {{ $groupReports->count() }} {{ Str::plural('report', $groupReports->count()) }}
                    · Updated {{ $groupReports->max('updated_at')->format('d M Y') }}
                    @else
                    No progress reports yet.
                    @endif
                </div>

            </div>

        </a>

    </div>

    @endforeach

</div>

@endsection