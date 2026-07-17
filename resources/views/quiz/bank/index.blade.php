@extends('layouts.app')

@section('title', 'Question Bank')

@section('page-title', 'Question Bank')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/qbank.css') }}">
@endpush

@section('content')

{{-- Header --}}
<div class="courses-header mb-4">

    <div>
        <h3 class="mb-1">
            Question Banks
        </h3>

        <p class="text-muted mb-0">
            Organize and reuse questions across quizzes.
        </p>
    </div>

    <button type="button"
        class="btn btn-theme"
        data-bs-toggle="modal"
        data-bs-target="#createQuestionBankModal">
    <i class="bi bi-plus-circle"></i>
    Create Question Bank
</button>

</div>

{{-- Search + Filter --}}
<form method="GET" action="{{ route('questionbank.index') }}" class="qbank-toolbar mb-4">

    <div class="qbank-search">
        <i class="bi bi-search"></i>
        <input
            type="text"
            name="search"
            value="{{ $search }}"
            class="form-control"
            placeholder="Search question banks..."
            onchange="this.form.submit()">
    </div>

    <div class="qbank-filter-pills">

        <a href="{{ route('questionbank.index', array_filter(['search' => $search])) }}"
            class="filter-pill {{ $filter === 'all' ? 'active' : '' }}">
            All
        </a>

        <a href="{{ route('questionbank.index', array_filter(['search' => $search, 'filter' => 'mine'])) }}"
            class="filter-pill {{ $filter === 'mine' ? 'active' : '' }}">
            Made by Me
        </a>

        <a href="{{ route('questionbank.index', array_filter(['search' => $search, 'filter' => 'others'])) }}"
            class="filter-pill {{ $filter === 'others' ? 'active' : '' }}">
            Made by Others
        </a>

    </div>

</form>

@if($questionBanks->count())

<div class="row g-4">

    @foreach($questionBanks as $questionBank)

        @php $isOwner = $questionBank->created_by === auth()->id(); @endphp

        <div class="col-lg-4 col-md-6">

            <div class="qbank-card">

                <div class="qbank-header">

                    <div class="qbank-icon">
                        <i class="bi bi-journal-text"></i>
                    </div>

                    @if(!$isOwner)
                    <span class="qbank-shared-tag">
                        <i class="bi bi-people-fill"></i>
                        Shared
                    </span>
                    @endif

                    <div class="qbank-overlay">
                        <a href="{{ route('questionbank.show', $questionBank->id) }}" class="btn btn-light btn-sm">View</a>
                    </div>

                </div>

                <div class="qbank-content">

                    <h4>{{ $questionBank->name }}</h4>

                    @if($questionBank->description)
                    <p>{{ $questionBank->description }}</p>
                    @else
                    <p>Question bank made by {{ $questionBank->creator->name }}</p>
                    @endif

                    <div class="qbank-badges">
                        <span class="qbank-badge">
                            <i class="bi bi-question-circle"></i>
                            @if($questionBank->questions_count)
                            {{ $questionBank->questions_count }} Questions
                            @else
                            0 Questions
                            @endif
                        </span>

                        <span class="qbank-badge">
                            <i class="bi bi-book"></i>
                            @if($questionBank->course_id)
                            From {{ $questionBank->course->name }}
                            @else
                            Independent Question Bank
                            @endif
                        </span>
                    </div>

                    <div class="qbank-footer">

                        <div class="qbank-author">
                            <i class="bi bi-person-fill"></i>
                            {{ $isOwner ? 'You' : $questionBank->creator->name }}
                        </div>

                        <div class="qbank-date">
                            {{ $questionBank->created_at->format('d M Y') }}
                        </div>

                    </div>

                </div>

            </div>

        </div>

    @endforeach

</div>

@else

<div class="empty-state">

    <i class="bi bi-journal-x"></i>

    <h5>No Question Banks Found</h5>

    <p class="text-muted">
        @if($search || $filter !== 'all')
        No question banks match your search or filter.
        @else
        Create your first Question Bank to start organizing reusable questions.
        @endif
    </p>

</div>

@endif

{{-- Create Question Bank Modal --}}
<div class="modal fade"
     id="createQuestionBankModal"
     tabindex="-1"
     aria-labelledby="createQuestionBankModalLabel"
     aria-hidden="true">

    <div class="modal-dialog modal-lg modal-dialog-scrollable">

        <div class="modal-content custom-modal">

            <form action="{{ route('questionbank.store') }}"
                  method="POST">

                @csrf

                <div class="modal-header">

                    <h5 class="modal-title"
                        id="createQuestionBankModalLabel">

                        <i class="bi bi-journal-text me-2"></i>
                        Create Question Bank

                    </h5>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal">
                    </button>

                </div>

                <div class="modal-body">

                    <div class="mb-4">
                        <label class="form-label">
                            Question Bank Name
                        </label>

                        <input type="text"
                               name="name"
                               class="form-control"
                               maxlength="255"
                               required
                               placeholder="e.g. HSK 1 Vocabulary Quiz">
                    </div>

                    <div class="mb-4">
                        <label class="form-label">
                            Description
                        </label>

                        <textarea
                            name="description"
                            class="form-control"
                            rows="5"
                            placeholder="Describe the purpose of this question bank..."></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">
                            Intended Course
                            <small class="text-muted">(Optional)</small>
                        </label>

                        <select name="course_id"
                                class="form-select">

                            <option value="">
                                -- None --
                            </option>

                            @foreach($courses as $course)

                                <option value="{{ $course->id }}">
                                    {{ $course->name }}
                                </option>

                            @endforeach

                        </select>

                        <small class="text-muted">
                            This is only used to indicate the course this
                            question bank was created for. It can still be
                            reused in any course.
                        </small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            Visibility
                        </label>

                        <select name="visibility"
                                class="form-select">

                            <option value="1" selected>
                                Visible
                            </option>

                            <option value="0">
                                Hidden
                            </option>

                        </select>

                        <small class="text-muted">
                            Visible banks can be found and reused by anyone
                            when building a quiz. Hidden ones are only usable
                            by you.
                        </small>
                    </div>

                </div>

                <div class="modal-footer">

                    <button type="button"
                            class="btn btn-secondary-custom"
                            data-bs-dismiss="modal">

                        Cancel

                    </button>

                    <button type="submit"
                            class="btn btn-theme">

                        <i class="bi bi-plus-circle me-1"></i>

                        Create Question Bank

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>
@endsection