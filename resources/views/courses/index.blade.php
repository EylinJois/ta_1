@extends('layouts.app')

@section('title', 'Courses')

@section('page-title', 'Courses')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/course.css') }}">
@endpush

@section('content')

{{-- Header --}}
<div class="courses-header mb-4">

    <div>

        <h3 class="mb-1">
            Mandarin Courses
        </h3>

        <p class="text-muted mb-0">
            Explore available Mandarin learning courses.
        </p>

    </div>

    <a href="#"
       class="btn btn-theme">
        <i class="bi bi-plus-circle"></i>
        Add Course
    </a>

</div>

{{-- Course List --}}
<div class="row g-4">

    @foreach($courses as $course)

        <div class="col-lg-4 col-md-6">

            <div class="course-card">

                {{-- Image --}}
                <div class="course-image-wrapper">

                    @if($course->img)
                        <img src="{{ asset('storage/' . $course->img) }}"
                             class="course-image">
                    @else
                        <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?q=80&w=1200"
                             class="course-image">
                    @endif

                    <div class="course-overlay">

                        <a href="{{ route('courses.show', $course->id) }}"
                           class="btn btn-light">
                            View Course
                        </a>

                    </div>

                </div>

                {{-- Content --}}
                <div class="course-content">

                    <h4>
                        {{ $course->name }}
                    </h4>

                    <p>
                        {{ Str::limit($course->description, 120) }}
                    </p>

                    <div class="course-footer">

                        <div class="course-stat">

                            <i class="bi bi-collection-fill"></i>

                            {{ $course->sections->count() }} Sections

                        </div>

                        <small class="text-muted">
                            {{ $course->created_at->format('d M Y') }}
                        </small>

                    </div>

                </div>

            </div>

        </div>

    @endforeach

</div>

@endsection