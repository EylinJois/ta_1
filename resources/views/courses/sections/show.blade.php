@extends('layouts.app')

@section('title', $section->name)

@section('page-title', 'Section Detail')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/course.css') }}">
@endpush

@section('content')

{{-- COURSE HEADER --}}
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
                    <i class="bi bi-journal-text"></i>

                    {{ $section->name }}
                </div>

                <div class="meta-item">
                    <i class="bi bi-collection-fill"></i>

                    {{ $section->materials ? $section->materials->count() : 0 }}
                    Materials
                </div>

            </div>

        </div>

        <div class="col-lg-4">

            @if(!empty($course->img))

            <img src="{{ asset('storage/' . $course->img) }}"
                class="detail-course-image">

            @else

            <img src="https://images.unsplash.com/photo-1516321318423-f06f85e504b3?q=80&w=1200"
                class="detail-course-image">

            @endif

        </div>

    </div>

</div>

{{-- MATERIALS --}}
<div class="table-card">

    <div class="courses-header mb-4">

        <a
            href="{{ route('courses.show',$course) }}"
            class="btn btn-secondary-custom">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h4 class="section-title mb-0">
            {{ $section->name }}
        </h4>
@if(Auth::user()->isAdmin($course->id) || Auth::user()->isTeacher($course->id))
        <a href="{{ route('materials.create', $section) }}"
            class="btn btn-theme">

            <i class="bi bi-plus-circle"></i>
            Add Material

        </a>
@endif
    </div>

    <div class="row g-4">

        @forelse($visibleMaterials as $material)

            <div class="col-lg-4">

                <a
                    href="{{ route('materials.show',$material) }}"
                    class="text-decoration-none text-dark">

                    <div class="material-display-card h-100">

                        <div class="material-card-header">

                            <div class="material-display-icon">
                                @switch($material->materialType->name)
                                @case('Youtube Video')
                                <i class="bi bi-youtube"></i>
                                @break

                                @case('PDF')
                                <i class="bi bi-file-earmark-pdf-fill"></i>
                                @break

                                @case('EPUB')
                                <i class="bi bi-book-fill"></i>
                                @break

                                @case('Page')
                                <i class="bi bi-file-earmark-richtext-fill"></i>
                                @break

                                @case('Link')
                                <i class="bi bi-link-45deg"></i>
                                @break

                                @default
                                <i class="bi bi-file-earmark-fill"></i>
                                @endswitch
                            </div>
@if(Auth::user()->isAdmin($course->id) || Auth::user()->isTeacher($course->id))
                            <div class="material-card-actions">

                                <a
                                    class="icon-action-btn edit-btn"
                                    href="{{ route('materials.edit', $material) }}">
                                    <i class="bi bi-pencil-fill"></i>
                                </a>

                                <form action="{{ route('materials.destroy', $material) }}"
                                    method="POST"
                                    onsubmit="return confirm('Delete this material?')">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit" class="icon-action-btn delete-btn">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </form>

                            </div>
@endif
                        </div>

                        <h5>

                            {{ $material->name }}

                        </h5>

                        <span class="material-type">

                            {{ $material->materialType->name }}

                        </span>

                        <p>

                            {{ Str::limit(
                        $material->description,
                        120
                    ) }}

                        </p>

                    </div>

                </a>

            </div>

        @empty

        <div class="col-12">

            <div class="empty-state">

                <i class="bi bi-folder-x"></i>

                <h4>
                    No Materials
                </h4>

                <p>
                    This section doesn't contain any learning materials yet.
                </p>

            </div>

        </div>

        @endforelse

    </div>

</div>

@endsection