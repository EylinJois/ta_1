@extends('layouts.app')

@section('title', 'Courses')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/course.css') }}">
@endpush

@section('content')

{{-- Header --}}
<div class="row mb-4">

    <div class="col">

        <h2>
            My Courses
        </h2>

    </div>

    <div class="col-auto">

        <a href="{{ route('courses.create') }}"
           class="btn btn-theme btn-sm">
            <i class="bi bi-plus-circle"></i>
            Add Course
        </a>

    </div>
</div>

@if(Auth::user()->classes()->count() > 0)
{{-- Course List --}}
<div class="row g-4">

    @forelse($classes as $class)
    @php $course = $class->course; @endphp

    <div class="col-lg-4 col-md-6">
        <div class="course-card">

            {{-- Image --}}
            <div class="course-image-wrapper">
                @if($course->imgMedia)
                    <img src="{{ Storage::disk($course->imgMedia->storage_disk)->url($course->imgMedia->file_path) }}" class="course-image">
                @else
                    <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?q=80&w=1200"
                         class="course-image">
                @endif

                <div class="course-overlay">
                    <a href="{{ route('courses.show', $course->id) }}" class="btn btn-light">
                        View Course
                    </a>
                </div>
            </div>

            {{-- Content --}}
            <div class="course-content">
                <h4>{{ $course->name }} - {{ $class->name }}</h4>

                <p>{{ Str::limit($course->description, 80) }}</p>

                <div class="course-footer">
                    <div class="course-stat">
                        <i class="bi bi-collection-fill"></i>
                        {{ $course->sections_count }} Sections
                    </div>

                    <small class="text-muted">
                        {{ $course->created_at->format('d M Y') }}
                    </small>
                </div>
            </div>

        </div>
    </div>

    @empty
    <div class="col-12 text-center text-muted py-5">
        You are not enrolled in any class yet.
    </div>
    @endforelse

</div>

@endif
@endsection