@extends('layouts.app')

@section('title', $section->name)

@push('styles')
<link rel="stylesheet" href="{{ asset('css/course.css') }}">
@endpush

@section('content')

{{-- COURSE HEADER --}}

        <a href="{{ route('courses.show',$course) }}"
            class="btn back-to-course-btn">
            <i class="bi bi-arrow-left"></i>
        </a>
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

                <div class="meta-item">
                    <i class="bi bi-patch-question-fill"></i>

                    {{ $visibleQuizzes->count() }}
                    Quizzes
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
<div class="table-card mb-4">

    <div class="courses-header mb-4 section-materials-header">
        <h4 class="section-title mb-0">
            {{ $section->name }}
        </h4>
        @if(Auth::user()->isManagerInCourse($course->id))
        <div class="d-flex gap-2 section-header-actions">

            <a href="{{ route('materials.create', $section) }}"
                class="btn btn-theme">
                <i class="bi bi-file-earmark-plus"></i>
                <span class="btn-label">Add Material</span>
            </a>

            <button type="button"
                class="btn btn-theme-yellow"
                data-bs-toggle="modal"
                data-bs-target="#addActivityModal">
                <i class="bi bi-patch-question-fill"></i>
                <span class="btn-label">Add Activity</span>
            </button>

        </div>
        @endif
    </div>

    <div class="row g-4">

        @forelse($visibleMaterials as $material)

        <div class="col-lg-4">
            <a href="{{ route('materials.show',$material) }}"
                class="text-decoration-none text-dark">
                <div class="material-display-card h-100">
                    <div class="material-card-header">
                        <div class="material-display-icon">
                            @switch($material->type)
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

                        @if(Auth::user()->isManagerInCourse($course->id))
                        <div class="material-card-actions">
                            <a class="icon-action-btn edit-btn"
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
                        @elseif($completedMaterialIds->contains($material->id))
                        <span class="completed-pill">
                            <i class="bi bi-check-circle-fill"></i>
                            Completed
                        </span>
                        @endif
                    </div>

                    <h5>
                        {{ $material->name }}
                    </h5>

                    <span class="material-type">
                        {{ $material->type }}
                    </span>

                    <p>
                        {{ Str::limit($material->description, 120) }}
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

{{-- ACTIVITIES --}}
<div class="table-card">

    <div class="courses-header mb-4">

        <h4 class="section-title mb-0">
            Activities
        </h4>

    </div>

    <div class="row g-4">

        @forelse($visibleActivities as $activity)

        <div class="col-lg-4">

            <a
                href="{{ $activity->activity_kind === 'quiz' ? route('quizzes.show', $activity) : route('assignments.show', $activity) }}"
                class="text-decoration-none text-dark">

                <div class="material-display-card h-100">

                    <div class="material-card-header">

                        <div class="material-display-icon">
                            @if($activity->activity_kind === 'quiz')
                            <i class="bi bi-patch-question-fill"></i>
                            @else
                            <i class="bi bi-file-earmark-text-fill"></i>
                            @endif
                        </div>

                        @if(Auth::user()->isManagerInCourse($course->id))
                        <div class="material-card-actions">

                            <a
                                class="icon-action-btn edit-btn"
                                href="{{ $activity->activity_kind === 'quiz' ? route('quizzes.edit', $activity) : route('assignments.edit', $activity) }}">
                                <i class="bi bi-pencil-fill"></i>
                            </a>

                            <form action="{{ $activity->activity_kind === 'quiz' ? route('quizzes.destroy', $activity) : route('assignments.destroy', $activity) }}"
                                method="POST"
                                onsubmit="return confirm('Delete this {{ $activity->activity_kind }}?')">
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
                        {{ $activity->name }}
                        @if(!$activity->visibility)
                        <span class="badge bg-secondary">Hidden</span>
                        @endif
                        @if($activity->activity_kind === 'assignment' && $activity->opened_at && now()->lt($activity->opened_at))
                        <span class="badge bg-info text-dark">Opens {{ $activity->opened_at->format('d M') }}</span>
                        @endif
                    </h5>

                    <span class="material-type">
                        {{ $activity->activity_kind === 'quiz' ? 'Quiz' : 'Assignment' }}
                    </span>

                    <p>
                        {{ Str::limit($activity->description, 120) }}
                    </p>

                    <div class="d-flex flex-wrap gap-2 mt-2">

                        @if($activity->activity_kind === 'quiz')

                        @if($activity->duration)
                        <span class="badge bg-light text-dark">
                            <i class="bi bi-clock"></i>
                            {{ gmdate('H:i:s', $activity->duration) }}
                        </span>
                        @endif

                        @if($activity->pass_score)
                        <span class="badge bg-light text-dark">
                            <i class="bi bi-check-circle"></i>
                            Pass at {{ rtrim(rtrim(number_format($activity->pass_score, 2), '0'), '.') }}
                        </span>
                        @endif

                        @else



                        @if($activity->due_at)
                        <span class="badge bg-light text-dark">
                            <i class="bi bi-calendar-event"></i>
                            Due {{ $activity->due_at->format('d M Y, H:i') }}
                        </span>
                        @endif


                        @endif

                    </div>

                </div>

            </a>

        </div>

        @empty

        <div class="col-12">

            <div class="empty-state">

                <i class="bi bi-clipboard-x"></i>

                <h4>
                    No Activities
                </h4>

                <p>
                    This section doesn't have any quizzes or assignments yet.
                </p>

            </div>

        </div>

        @endforelse

    </div>

</div>
@include('courses.sections.add-activity-modal')
@endsection