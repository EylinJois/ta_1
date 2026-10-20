@extends('layouts.app')

@section('title', $questionBank->name)

@section('page-title', 'Question Bank')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/qbank.css') }}">
@endpush


@section('content')

<div class="mb-4">
    <a href="{{ route('questionbank.index') }}" class="btn btn-back">
        <i class="bi bi-arrow-left"></i>
    </a>
</div>

@session('error')
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endsession

{{-- Banner --}}
<div class="row align-items-center question-bank-banner mb-4">

    <div class="col-lg-8">

        <div>

            <span class="course-badge">
                Question Bank
            </span>

            <h2>{{ $questionBank->name }}</h2>

            <p class="mb-0">
                {{ $questionBank->description }}
            </p>
            <div class="course-meta">
                <div class="meta-item">
                    <i class="bi bi-clipboard-check-fill"></i>
                    Use code: QB{{ $questionBank->id}}
                </div>
            </div>

        </div>

        <div class="d-flex flex-wrap gap-3 mt-4 align-items-center">

            @if(auth()->id() == $questionBank->created_by)

            <button class="btn btn-light"
                data-bs-toggle="modal"
                data-bs-target="#editQuestionBankModal">

                <i class="bi bi-pencil-fill"></i>
                Edit

            </button>

            <form action="{{ route('questionbank.destroy', $questionBank) }}"
                method="POST"
                onsubmit="return confirm('Delete this Question Bank? All questions inside it will also be deleted.')">

                @csrf
                @method('DELETE')

                <button class="btn btn-dark-custom">

                    <i class="bi bi-trash-fill"></i>
                    Delete
                </button>

            </form>

            @endif

        </div>

    </div>
</div>

{{-- Statistics --}}
<div class="row g-3 mb-4">

    <div class="col-md-3">

        @if($questionBank->questions->count() > 0)
        <div class="stat-card">
            <h3>{{ $questionBank->questions->count() }}</h3>
            <span>Total Questions</span>
        </div>
        @endif

    </div>

    <div class="col-md-3">

        @if($questionBank->questions->count() > 0)
        <div class="stat-card">
            <h3>{{ rtrim(rtrim(number_format($questionBank->questions->sum('point'), 2), '0'), '.') }}</h3>
            <span>Total Score</span>
        </div>
        @endif

    </div>

</div>

{{-- Type Filter --}}
@if($questionBank->questions->count() > 0)
<div class="type-filter-pills mb-4" id="typeFilterPills">

    @php
    $typeCounts = $questionBank->questions->countBy('type');
    $typeLabels = [
    'choice' => 'Multiple Choice',
    'multiple_select' => 'Multiple Select',
    'written' => 'Written',
    'short_answer' => 'Short Answer',
    'file' => 'File Upload',
    'both' => 'Written + File',
    ];
    @endphp

    <button type="button" class="type-pill active" data-type="all">
        All <span class="type-pill-count">{{ $questionBank->questions->count() }}</span>
    </button>

    @foreach($typeLabels as $typeValue => $typeLabel)
    @if(($typeCounts[$typeValue] ?? 0) > 0)
    <button type="button" class="type-pill" data-type="{{ $typeValue }}">
        {{ $typeLabel }} <span class="type-pill-count">{{ $typeCounts[$typeValue] }}</span>
    </button>
    @endif
    @endforeach

</div>
@endif

{{-- Sections Header --}}
<div class="courses-header mb-4">

    <h4 class="section-title mb-0">
        Question List
    </h4>
    <button class="btn btn-theme add-section-btn"
        data-bs-toggle="modal"
        data-bs-target="#createQuestionModal">

        <i class="bi bi-plus-circle"></i>
        Add Question

    </button>
</div>

{{-- Questions --}}
<div class="question-list" id="questionList">
    @if($questionBank->questions)
    @forelse($questionBank->questions->sortBy('order_index') as $question)

    <div class="question-item"
        data-id="{{ $question->id }}"
        data-type="{{ $question->type }}">

        <div class="question-left">

            <div class="drag-handle">
                <i class="bi bi-list"></i>
            </div>

            <div class="question-icon">

                @switch($question->type)

                @case('choice')
                <i class="bi bi-record-circle"></i>
                @break

                @case('multiple_select')
                <i class="bi bi-check2-square"></i>
                @break

                @case('written')
                <i class="bi bi-pencil-square"></i>
                @break

                @case('file')
                <i class="bi bi-file-earmark-arrow-up"></i>
                @break

                @default
                <i class="bi bi-ui-checks"></i>

                @endswitch

            </div>

            <div>

                <h5>{{ Str::limit($question->question,120) }}</h5>

                <div class="question-meta">

                    <span class="badge bg-light text-dark">

                        {{ ucfirst(str_replace('_',' ',$question->type)) }}

                    </span>

                </div>

                <div class="question-meta">

                    <span class="badge bg-warning text-dark">

                        {{ rtrim(rtrim(number_format($question->point, 2), '0'), '.') }} pts

                    </span>

                </div>

            </div>

        </div>

        <div class="question-actions">

            <button
                class="icon-action-btn edit-btn"
                data-bs-toggle="modal"
                data-bs-target="#editQuestionModal{{ $question->id }}"
                data-id="{{ $question->id }}">

                <i class="bi bi-pencil"></i>

            </button>

            <form action="{{ route('questions.destroy', $question) }}"
                method="POST"
                onsubmit="return confirm('Delete this question? This cannot be undone.')">
                @csrf
                @method('DELETE')

                <button type="submit" class="icon-action-btn delete-btn">
                    <i class="bi bi-trash-fill"></i>
                </button>

            </form>

        </div>

    </div>

    {{-- Render this question's own edit modal right here, once per row --}}
    @include('quiz.questions.modal', ['questionBank' => $questionBank, 'question' => $question])

    @empty

    <div class="empty-state">

        <i class="bi bi-question-circle"></i>

        <h4>No Questions Yet</h4>

        <p>
            Start building your question bank by adding your first question.
        </p>

    </div>

    @endforelse
    @endif

    {{-- Shown when a type filter matches nothing (all rows hidden by JS) --}}
    <div class="empty-state d-none" id="noTypeMatch">

        <i class="bi bi-funnel"></i>

        <h4>No Questions of This Type</h4>

        <p>
            Try a different filter to see other questions.
        </p>

    </div>

</div>

@include('quiz.questions.modal', ['questionBank' => $questionBank, 'question' => null])
@include('quiz.bank.edit')
@endsection


@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {

        /* ========== TYPE FILTER ========== */

        const typeFilterPills = document.getElementById('typeFilterPills');
        const questionList = document.getElementById('questionList');
        const noTypeMatch = document.getElementById('noTypeMatch');

        if (typeFilterPills) {

            typeFilterPills.addEventListener('click', function(e) {

                const pill = e.target.closest('.type-pill');
                if (!pill) return;

                typeFilterPills.querySelectorAll('.type-pill').forEach(p => p.classList.remove('active'));
                pill.classList.add('active');

                const selectedType = pill.dataset.type;
                const items = questionList.querySelectorAll('.question-item');
                let visibleCount = 0;

                items.forEach(function(item) {
                    const matches = selectedType === 'all' || item.dataset.type === selectedType;
                    item.classList.toggle('d-none', !matches);
                    if (matches) visibleCount++;
                });

                noTypeMatch.classList.toggle('d-none', visibleCount > 0);

            });

        }

        /* ========== DRAG TO REORDER (order_index) ========== */

        if (questionList && typeof Sortable !== 'undefined') {

            Sortable.create(questionList, {

                animation: 200,

                handle: '.drag-handle',

                // Only .question-item rows have a .drag-handle, so the
                // empty-state placeholders (#noTypeMatch / "No Questions Yet")
                // can never be picked up - no extra filter needed.

                onEnd: function() {

                    let order = [];

                    questionList.querySelectorAll('.question-item')
                        .forEach((item, index) => {

                            order.push({
                                id: item.dataset.id,
                                order_idx: index + 1
                            });

                        });

                    fetch("{{ route('questionbank.questions.reorder', $questionBank) }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            questions: order
                        })
                    });
                }
            });

        }
    });
</script>
@endpush