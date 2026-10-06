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
    @include('quiz.questions.edit', ['question' => $question])

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

@include('quiz.questions.create')
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

        /* ========== QUESTION MODAL (create / edit) ========== */

        function initQuestionModal(modal) {

            const typeSelect = modal.querySelector('.question-type-select');

            const optionsSection = modal.querySelector('.options-section');
            const answersSection = modal.querySelector('.answers-section');
            const fileSection = modal.querySelector('.file-section');
            const choiceHint = modal.querySelector('.choice-hint');

            const optionsContainer = modal.querySelector('.options-container');
            const answersContainer = modal.querySelector('.answers-container');

            const addOptionBtn = modal.querySelector('.add-option-btn');
            const addAnswerBtn = modal.querySelector('.add-answer-btn');

            // Read seed data up front — before anything else references it.
            const seedOptions = JSON.parse(modal.dataset.seedOptions || '[]');
            const seedAnswers = JSON.parse(modal.dataset.seedAnswers || '[]');

            let answerRowCounter = 0;

            const seedFileConfig = JSON.parse(modal.dataset.seedFileConfig || '{}');

            /* ========== SHOW / HIDE + ENABLE / DISABLE SECTIONS ========== */

            function setSectionState(section, visible) {
                section.classList.toggle('d-none', !visible);
                section.querySelectorAll('input, textarea, select').forEach(function(field) {
                    field.disabled = !visible;
                });
            }

            function refreshSections() {

                setSectionState(optionsSection, false);
                setSectionState(answersSection, false);
                setSectionState(fileSection, false);
                choiceHint.classList.add('d-none');

                switch (typeSelect.value) {
                    case 'choice':
                        setSectionState(optionsSection, true);
                        choiceHint.classList.remove('d-none');
                        break;
                    case 'multiple_select':
                        setSectionState(optionsSection, true);
                        break;
                    case 'short_answer':
                        setSectionState(answersSection, true);
                        break;
                    case 'file':
                        setSectionState(fileSection, true);
                        break;
                    case 'both':
                        setSectionState(fileSection, true);
                        break;
                    case 'written':
                    default:
                        break;
                }
            }

            typeSelect.addEventListener('change', refreshSections);

            /* ========== QUESTION OPTIONS ========== */

            function updateCorrectValues() {
                optionsContainer.querySelectorAll('.option-row').forEach(function(row, index) {
                    row.querySelector('.correct-option').value = index;
                });
            }

            function enforceSingleCorrect(changedCheckbox) {
                if (typeSelect.value !== 'choice') return;
                optionsContainer.querySelectorAll('.correct-option').forEach(function(cb) {
                    if (cb !== changedCheckbox) cb.checked = false;
                });
            }

            function createOption(text = '', isCorrect = false) {

                const option = document.createElement('div');
                option.className = 'option-row card mb-3';

                option.innerHTML = `
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <input type="text" class="form-control" name="option_text[]"
                            placeholder="Option text" value="${text.replace(/"/g, '&quot;')}" required>
                    </div>
                    <div class="col-md-2">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input correct-option"
                                name="is_correct[]" value="0" ${isCorrect ? 'checked' : ''}>
                            <label class="form-check-label">Correct</label>
                        </div>
                    </div>
                    <div class="col-md-2 text-end">
                        <button type="button" class="btn btn-danger remove-option">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;

                optionsContainer.appendChild(option);
                updateCorrectValues();
            }

            addOptionBtn.addEventListener('click', () => createOption());

            optionsContainer.addEventListener('click', function(e) {
                if (e.target.closest('.remove-option')) {
                    if (optionsContainer.querySelectorAll('.option-row').length <= 2) return;
                    e.target.closest('.option-row').remove();
                    updateCorrectValues();
                }
            });

            optionsContainer.addEventListener('change', function(e) {
                if (e.target.classList.contains('correct-option')) {
                    enforceSingleCorrect(e.target);
                }
            });

            /* ========== ACCEPTED ANSWERS ========== */

            function createAnswer(answer = '', ignoreCase = true, ignoreSpace = true) {

                const rowIndex = answerRowCounter++;

                const row = document.createElement('div');
                row.className = 'accepted-answer-row card mb-3';

                row.innerHTML = `
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <input type="text" class="form-control" name="accepted_answers[${rowIndex}]"
                            placeholder="Accepted answer" value="${answer.replace(/"/g, '&quot;')}" required>
                    </div>
                    <div class="col-md-2">
                        <div class="form-check">
                            <input type="hidden" name="ignore_case[${rowIndex}]" value="0">
                            <input type="checkbox" class="form-check-input" name="ignore_case[${rowIndex}]"
                                value="1" ${ignoreCase ? 'checked' : ''}>
                            <label class="form-check-label small">Ignore case</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-check">
                            <input type="hidden" name="ignore_space[${rowIndex}]" value="0">
                            <input type="checkbox" class="form-check-input" name="ignore_space[${rowIndex}]"
                                value="1" ${ignoreSpace ? 'checked' : ''}>
                            <label class="form-check-label small">Ignore spaces</label>
                        </div>
                    </div>
                    <div class="col-md-2 text-end">
                        <button type="button" class="btn btn-danger remove-answer">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;

                answersContainer.appendChild(row);
            }

            addAnswerBtn.addEventListener('click', () => createAnswer());

            answersContainer.addEventListener('click', function(e) {
                if (e.target.closest('.remove-answer')) {
                    if (answersContainer.querySelectorAll('.accepted-answer-row').length <= 1) return;
                    e.target.closest('.accepted-answer-row').remove();
                }
            });

            /* ========== FILE UPLOAD CONFIG ========== */

            const fileTypeCheckboxes = modal.querySelectorAll('.file-type-checkbox');
            const fileAllowedCustom = modal.querySelector('.file-allowed-custom');
            const fileAllowedJson = modal.querySelector('.file-allowed-json');

            function syncFileAllowedJson() {

                const checked = Array.from(fileTypeCheckboxes)
                    .filter(cb => cb.checked)
                    .map(cb => cb.value);

                const custom = fileAllowedCustom.value
                    .split(',')
                    .map(s => s.trim().toLowerCase().replace(/^\./, ''))
                    .filter(Boolean);

                // De-duplicate in case someone types an extension that's also a checkbox.
                const combined = [...new Set([...checked, ...custom])];

                fileAllowedJson.value = JSON.stringify(combined);
            }

            fileTypeCheckboxes.forEach(cb => cb.addEventListener('change', syncFileAllowedJson));
            fileAllowedCustom.addEventListener('input', syncFileAllowedJson);

            function applySeedFileConfig() {

                const allowed = seedFileConfig.allowed || [];
                const knownExtensions = Array.from(fileTypeCheckboxes).map(cb => cb.value);

                fileTypeCheckboxes.forEach(cb => {
                    cb.checked = allowed.includes(cb.value);
                });

                const customExtensions = allowed.filter(ext => !knownExtensions.includes(ext));
                fileAllowedCustom.value = customExtensions.join(', ');

                syncFileAllowedJson();

                const maxFileSizeInput = modal.querySelector('input[name="max_file_size"]');
                const maxFilesInput = modal.querySelector('input[name="max_files"]');

                if (seedFileConfig.max_file_size) maxFileSizeInput.value = seedFileConfig.max_file_size;
                if (seedFileConfig.max_files) maxFilesInput.value = seedFileConfig.max_files;
            }

            applySeedFileConfig();

            /* ========== SEED, THEN APPLY VISIBILITY (in that order, once) ========== */

            if (seedOptions.length) {
                seedOptions.forEach(o => createOption(o.text, !!o.is_correct));
            } else {
                createOption();
                createOption();
            }

            if (seedAnswers.length) {
                seedAnswers.forEach(a => createAnswer(a.answer, !!a.ignore_case, !!a.ignore_space));
            } else {
                createAnswer();
            }

            // Runs exactly once, after every row (seeded or default) already exists,
            // so it correctly disables whatever ends up hidden.
            refreshSections();
        }

        document.querySelectorAll('.question-modal').forEach(initQuestionModal);

    });
</script>
@endpush