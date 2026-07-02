@extends('layouts.app')

@section('title', $course->name)

@section('page-title', 'Course Detail')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/course.css') }}">
@endpush

@section('content')

{{-- Banner --}}
<div class="course-detail-banner mb-4">

    <div class="row align-items-center">

        <div class="col-lg-8">

            <span class="course-badge">
                Mandarin Learning Course
            </span>

            <h2>
                {{ $course->name }}
            </h2>

            <p>
                {{ $course->description }}
            </p>

            <div class="course-meta">

                <div class="meta-item">
                    <i class="bi bi-collection-fill"></i>
                    {{ $course->sections->count() }} Sections
                </div>

                <div class="meta-item">
                    <i class="bi bi-calendar-fill"></i>
                    {{ $course->created_at->format('d M Y') }}
                </div>

            </div>

        </div>

        <div class="col-lg-4">

            @if($course->img)

                <img src="{{ asset('storage/' . $course->img) }}"
                     class="detail-course-image">

            @else

                <img src="https://images.unsplash.com/photo-1516321318423-f06f85e504b3?q=80&w=1200"
                     class="detail-course-image">

            @endif

        </div>

    </div>

</div>

{{-- Sections --}}
<div class="table-card">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <h4 class="section-title mb-0">
            Course Sections
        </h4>

        <a href="#"
           class="btn btn-theme">
            <i class="bi bi-plus-circle"></i>
            Add Section
        </a>

    </div>

    @forelse($course->sections as $section)

        <div class="section-item">

            <div class="section-left">

                <div class="section-icon">
                    <i class="bi bi-journal-text"></i>
                </div>

                <div>

                    <h5 class="mb-1">
                        {{ $section->name }}
                    </h5>

                    <div class="section-status">

                        @if($section->visibility)

                            <span class="badge bg-success">
                                Visible
                            </span>

                        @else

                            <span class="badge bg-secondary">
                                Hidden
                            </span>

                        @endif

                    </div>

                </div>

            </div>

            <div class="section-actions">

                <a href="#"
                   class="btn btn-sm btn-orange">
                    <i class="bi bi-pencil-fill"></i>
                </a>

                <a href="#"
                   class="btn btn-sm btn-danger">
                    <i class="bi bi-trash-fill"></i>
                </a>

            </div>

        </div>

    @empty

        <div class="empty-state">

            <i class="bi bi-journal-x"></i>

            <h5>
                No Sections Yet
            </h5>

            <p>
                Start creating sections for this course.
            </p>

        </div>

    @endforelse

</div>

@endsection