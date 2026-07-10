@extends('layouts.app')

@section('title', $course->name)

@section('page-title', 'Course Detail')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/course.css') }}">
@endpush

@section('content')

{{-- Course Banner --}}
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

                    {{ $course->sections ? $course->sections->count() : 0 }}
                    Sections
                </div>

                <div class="meta-item">
                    <i class="bi bi-people-fill"></i>

                    {{ $course->users ? $course->users->count() : 0 }}
                    Participants
                </div>

                <div class="meta-item">
                    <i class="bi bi-calendar-fill"></i>

                    {{ $course->created_at ? $course->created_at->format('d M Y') : '-' }}
                </div>

            </div>

            {{-- Actions --}}
            <div class="d-flex flex-wrap gap-3 mt-4 align-items-center">

                <a href="{{ route('courses.participants.index', $course->id) }}"
                    class="btn btn-dark-custom">

                    <i class="bi bi-people-fill"></i>
                    Participants

                </a>

                <a href="{{ route('courses.edit', $course->id) }}"
                    class="btn btn-light">

                    <i class="bi bi-pencil-square"></i>
                    Edit Course

                </a>

                {{-- Delete Course --}}
                <form action="{{ route('courses.destroy', $course->id) }}"
                    method="POST"
                    onsubmit="return confirm('Delete this course permanently?')">

                    @csrf
                    @method('DELETE')

                    <button type="submit"
                        class="btn btn-danger">

                        <i class="bi bi-trash-fill"></i>
                        Delete Course

                    </button>

                </form>
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

{{-- Sections Header --}}
<div class="courses-header mb-4">

    <h4 class="section-title mb-0">
        Course Sections
    </h4>

    <button class="btn btn-theme add-section-btn"
        data-bs-toggle="modal"
        data-bs-target="#addSectionModal">

        <i class="bi bi-plus-circle"></i>
        Add Section

    </button>

</div>


{{-- Sections --}}
@if($course->sections && $course->sections->count() > 0)

<div id="sectionsContainer">
    @foreach($course->sections as $section)
    <a href="{{ route('sections.show', $section->id) }}"
        class="section-link-wrapper">
        <div class="section-item" data-id="{{ $section->id }}">

            <div class="section-top">

                <div class="section-left">

                    <div class="drag-handle"
                        onclick="event.preventDefault()">
                        <i class="bi bi-grip-vertical"></i>
                    </div>

                    <div class="section-icon">
                        <i class="bi bi-journal-text"></i>
                    </div>

                    <div>

                        <h5 class="mb-1">
                            {{ $section->name ?? '-' }}
                        </h5>

                        @if(isset($section->visibility) && $section->visibility)

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

                <div class="section-actions" onclick="event.stopPropagation()">


                    {{-- Edit Section --}}
                    <button type="button"
                        class="icon-action-btn edit-btn"
                        data-bs-toggle="modal"
                        data-bs-target="#editSectionModal{{ $section->id }}">

                        <i class="bi bi-pencil-fill"></i>

                    </button>

                    {{-- Delete Section --}}
                    <form action="{{ route('sections.destroy', $section->id) }}"
                        method="POST"
                        onsubmit="return confirm('Delete this section?')">

                        @csrf
                        @method('DELETE')

                        <button type="submit"
                            class="icon-action-btn delete-btn">

                            <i class="bi bi-trash-fill"></i>

                        </button>

                    </form>

                </div>

            </div>



        </div>
    </a>



    {{-- =========================
     EDIT SECTION MODAL
========================= --}}
    <div class="modal fade"
        id="editSectionModal{{ $section->id }}"
        tabindex="-1">

        <div class="modal-dialog modal-lg modal-dialog-centered">

            <div class="modal-content custom-modal">

                <div class="modal-header border-0">

                    <h4 class="modal-title">
                        Edit Section
                    </h4>

                    <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal">
                    </button>

                </div>

                <form action="{{ route('sections.update', $section->id) }}"
                    method="POST">

                    @csrf
                    @method('PUT')

                    <div class="modal-body">

                        {{-- Section Name --}}
                        <div class="mb-4">

                            <label class="form-label">
                                Section Name
                            </label>

                            <input type="text"
                                name="name"
                                class="form-control"
                                value="{{ $section->name }}"
                                required>

                        </div>

                        {{-- Visibility --}}
                        <div class="mb-4">

                            <label class="form-label">
                                Visibility
                            </label>

                            <select name="visibility"
                                class="form-select"
                                required>

                                <option value="1"
                                    {{ $section->visibility ? 'selected' : '' }}>

                                    Visible

                                </option>

                                <option value="0"
                                    {{ !$section->visibility ? 'selected' : '' }}>

                                    Hidden

                                </option>

                            </select>

                        </div>

                    </div>

                    <div class="modal-footer border-0">

                        <button type="button"
                            class="btn btn-secondary-custom"
                            data-bs-dismiss="modal">

                            Cancel

                        </button>

                        <button type="submit"
                            class="btn btn-theme">

                            <i class="bi bi-check-circle"></i>
                            Update Section

                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>
    @endforeach
</div>
@else
<div class="empty-section">
    <i class="bi bi-journal-text"></i>
    <p>No sections available</p>
</div>
@endif
{{-- =========================
     ADD SECTION MODAL
========================= --}}
<div class="modal fade"
    id="addSectionModal"
    tabindex="-1">

    <div class="modal-dialog modal-lg modal-dialog-centered">

        <div class="modal-content custom-modal">

            <div class="modal-header border-0">

                <h4 class="modal-title">
                    Add New Section
                </h4>

                <button type="button"
                    class="btn-close"
                    data-bs-dismiss="modal">
                </button>

            </div>

            <form action="{{ route('sections.store') }}"
                method="POST">

                @csrf

                <input type="hidden"
                    name="course_id"
                    value="{{ $course->id }}">

                <div class="modal-body">

                    {{-- Section Name --}}
                    <div class="mb-4">

                        <label class="form-label">
                            Section Name
                        </label>

                        <input type="text"
                            name="name"
                            class="form-control"
                            placeholder="Enter section name"
                            required>

                    </div>

                    {{-- Visibility --}}
                    <div class="mb-4">

                        <label class="form-label">
                            Visibility
                        </label>

                        <select name="visibility"
                            class="form-select"
                            required>

                            <option value="1">
                                Visible
                            </option>

                            <option value="0">
                                Hidden
                            </option>

                        </select>

                    </div>

                </div>

                <div class="modal-footer border-0">

                    <button type="button"
                        class="btn btn-secondary-custom"
                        data-bs-dismiss="modal">

                        Cancel

                    </button>

                    <button type="submit"
                        class="btn btn-theme">

                        <i class="bi bi-check-circle"></i>
                        Create Section

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        const container = document.getElementById('sectionsContainer');

        Sortable.create(container, {

            animation: 200,

            handle: '.drag-handle',

            onEnd: function() {

                let order = [];

                document.querySelectorAll('#sectionsContainer .section-item')
                    .forEach((item, index) => {

                        order.push({
                            id: item.dataset.id,
                            order_idx: index + 1
                        });

                    });

                fetch("{{ route('sections.reorder') }}", {

                    method: 'POST',

                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },

                    body: JSON.stringify({
                        sections: order
                    })

                });

            }

        });

    });
</script>

@endsection