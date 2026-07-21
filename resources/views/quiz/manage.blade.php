@extends('layouts.app')

@section('title', 'Manage Questions - ' . $quiz->name)

@section('page-title', 'Quiz Questions')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/qbank.css') }}">
@endpush

@section('content')

<div class="d-flex justify-content-between align-items-start mb-4">

    <div class="d-flex align-items-center gap-3">

        <a href="{{ route('sections.show', $quiz->section) }}" class="btn btn-secondary-custom">
            <i class="bi bi-arrow-left"></i>
        </a>

        <div>
            <h2 class="mb-0">{{ $quiz->name }}</h2>
            <p class="text-muted mb-0">Add and order the questions this quiz will use.</p>
        </div>

    </div>

    <button type="button"
        class="btn btn-warning text-white"
        data-bs-toggle="modal"
        data-bs-target="#editQuizModal">
        <i class="bi bi-pencil-fill"></i>
        Edit Quiz
    </button>

</div>

<div class="row g-4">

    {{-- LEFT: pick questions --}}
    <div class="col-lg-6">

        <div class="table-card mb-4">

            <h5 class="mb-3">
                <i class="bi bi-magic"></i>
                Random Pick
            </h5>

            <form id="randomPickForm">
                @csrf

                <div class="mb-3">
                    <label class="form-label">Question Bank</label>
                    <select class="form-select" name="question_bank_id" required>
                        <option value="" disabled selected>Choose a bank...</option>
                        @foreach($questionBanks as $bank)
                        <option value="{{ $bank->id }}">
                            {{ $bank->name }} ({{ $bank->questions_count }} questions)
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">How many?</label>
                        <input type="number" class="form-control" name="count" min="1" max="100" value="5" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Type (optional)</label>
                        <select class="form-select" name="type">
                            <option value="">Any type</option>
                            <option value="choice">Multiple Choice</option>
                            <option value="multiple_select">Multiple Select</option>
                            <option value="written">Written</option>
                            <option value="short_answer">Short Answer</option>
                            <option value="file">File Upload</option>
                            <option value="both">Written + File</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn btn-theme w-100">
                    <i class="bi bi-shuffle"></i>
                    Add Random Questions
                </button>
            </form>

        </div>

        <div class="table-card">

            <h5 class="mb-3">
                <i class="bi bi-search"></i>
                Pick Manually
            </h5>

            <div class="row g-2 mb-3">
                <div class="col-md-6">
                    <select class="form-select" id="filterBank">
                        <option value="">All banks</option>
                        @foreach($questionBanks as $bank)
                        <option value="{{ $bank->id }}">{{ $bank->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <input type="text" class="form-control" id="searchQuestions" placeholder="Search questions...">
                </div>
            </div>

            <div id="availableList" class="mb-3" style="max-height: 400px; overflow-y: auto;">
                <p class="text-muted text-center py-3">Loading questions...</p>
            </div>

            <button type="button" id="addSelectedBtn" class="btn btn-theme w-100" disabled>
                <i class="bi bi-plus-circle"></i>
                Add Selected (<span id="selectedCount">0</span>)
            </button>

        </div>

    </div>

    {{-- RIGHT: current quiz questions --}}
    <div class="col-lg-6">

        <div class="table-card">

            <div class="d-flex justify-content-between align-items-center mb-3">

                <h5 class="mb-0">
                    <i class="bi bi-list-ol"></i>
                    Quiz Questions (<span id="quizQuestionCount">{{ $quiz->questions->count() }}</span>)
                </h5>

                <button type="button"
                    class="btn btn-theme btn-sm"
                    data-bs-toggle="modal"
                    data-bs-target="#createStandaloneQuestionModal">
                    <i class="bi bi-plus-circle"></i>
                    Add Question
                </button>

            </div>

            <div id="quizQuestionList">
                @forelse($quiz->questions as $question)
                <div class="quiz-question-row card mb-2" data-question-id="{{ $question->id }}">
                    <div class="card-body d-flex align-items-center gap-3 py-2">

                        <div class="drag-handle">
                            <i class="bi bi-list"></i>
                        </div>

                        <div class="flex-grow-1">
                            <div>{{ Str::limit($question->question, 80) }}</div>
                            <span class="badge bg-light text-dark">
                                {{ ucfirst(str_replace('_', ' ', $question->type)) }}
                            </span>
                            @if(is_null($question->question_bank_id))
                            <span class="badge bg-warning text-dark">
                                Quiz-only
                            </span>
                            @else
                            <span class="badge bg-warning text-dark" title="Edit this question from its question bank">
                                <i class="bi bi-lock-fill"></i>
                                From bank
                            </span>
                            @endif
                        </div>

                        <div style="width: 100px;">
                            <span class="badge rounded-pill bg-light text-danger border border-danger px-3 py-2 w-100">
                                {{ $question->pivot->point_override ?? $question->point }} pt
                            </span>
                        </div>

                        @if(is_null($question->question_bank_id))
                        <button type="button"
                            class="icon-action-btn edit-btn"
                            data-bs-toggle="modal"
                            data-bs-target="#editStandaloneQuestionModal{{ $question->id }}">
                            <i class="bi bi-pencil-fill"></i>
                        </button>
                        @else
                        <button type="button"
                            class="icon-action-btn edit-btn"
                            data-bs-toggle="modal"
                            data-bs-target="#editPointModal{{ $question->id }}">
                            <i class="bi bi-pencil-fill"></i>
                        </button>
                        @endif

                        <button type="button" class="icon-action-btn delete-btn remove-question-btn">
                            <i class="bi bi-trash-fill"></i>
                        </button>

                    </div>
                </div>

                @if(is_null($question->question_bank_id))
                @include('quiz.questions.edit-standalone', ['question' => $question])
                @endif

                @empty
                <p class="text-muted text-center py-4" id="emptyQuizQuestions">
                    No questions added yet. Use the panel on the left to add some.
                </p>
                @include('quiz.edit-point')
                @endforelse
            </div>

        </div>

    </div>

</div>

@include('quiz.questions.create-standalone')
@include('quiz.edit-quiz')

@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {

        /* ===================== QUIZ QUESTION MANAGEMENT (existing) ===================== */

        const quizId = {
            {
                $quiz - > id
            }
        };
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        const availableList = document.getElementById('availableList');
        const selectedCountEl = document.getElementById('selectedCount');
        const addSelectedBtn = document.getElementById('addSelectedBtn');
        const quizQuestionList = document.getElementById('quizQuestionList');
        const quizQuestionCount = document.getElementById('quizQuestionCount');
        const filterBank = document.getElementById('filterBank');
        const searchInput = document.getElementById('searchQuestions');

        let selectedIds = new Set();
        let searchDebounce;

        function loadAvailable() {

            const params = new URLSearchParams();
            if (filterBank.value) params.set('question_bank_id', filterBank.value);
            if (searchInput.value) params.set('search', searchInput.value);

            fetch(`/quizzes/${quizId}/questions/available?${params.toString()}`)
                .then(r => r.json())
                .then(questions => {

                    if (questions.length === 0) {
                        availableList.innerHTML = '<p class="text-muted text-center py-3">No matching questions.</p>';
                        return;
                    }

                    availableList.innerHTML = questions.map(q => `
                    <div class="form-check border-bottom py-2">
                        <input
                            class="form-check-input available-question-checkbox"
                            type="checkbox"
                            value="${q.id}"
                            id="q${q.id}"
                            ${selectedIds.has(q.id) ? 'checked' : ''}>
                        <label class="form-check-label d-block" for="q${q.id}">
                            ${escapeHtml(q.question).slice(0, 100)}
                            <span class="badge bg-light text-dark ms-1">${q.type.replace('_', ' ')}</span>
                        </label>
                    </div>
                `).join('');
                });
        }

        function escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        filterBank.addEventListener('change', loadAvailable);

        searchInput.addEventListener('input', function() {
            clearTimeout(searchDebounce);
            searchDebounce = setTimeout(loadAvailable, 300);
        });

        availableList.addEventListener('change', function(e) {
            if (!e.target.classList.contains('available-question-checkbox')) return;

            const id = parseInt(e.target.value, 10);
            if (e.target.checked) {
                selectedIds.add(id);
            } else {
                selectedIds.delete(id);
            }

            selectedCountEl.textContent = selectedIds.size;
            addSelectedBtn.disabled = selectedIds.size === 0;
        });

        loadAvailable();

        addSelectedBtn.addEventListener('click', function() {

            fetch(`/quizzes/${quizId}/questions`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        question_ids: Array.from(selectedIds)
                    }),
                })
                .then(r => r.ok ? window.location.reload() : Promise.reject())
                .catch(() => alert('Failed to add questions. Please try again.'));
        });

        document.getElementById('randomPickForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const payload = Object.fromEntries(formData.entries());

            fetch(`/quizzes/${quizId}/questions/random`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(payload),
                })
                .then(r => r.ok ? window.location.reload() : Promise.reject())
                .catch(() => alert('Failed to add random questions. Please try again.'));
        });

        quizQuestionList.addEventListener('click', function(e) {

            const btn = e.target.closest('.remove-question-btn');
            if (!btn) return;

            if (!confirm('Remove this question from the quiz?')) return;

            const row = btn.closest('.quiz-question-row');
            const questionId = row.dataset.questionId;

            fetch(`/quizzes/${quizId}/questions/${questionId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                })
                .then(r => r.ok ? window.location.reload() : Promise.reject())
                .catch(() => alert('Failed to remove question. Please try again.'));
        });

        let pointOverrideDebounce;

        quizQuestionList.addEventListener('input', function(e) {
            if (!e.target.classList.contains('point-override-input')) return;

            clearTimeout(pointOverrideDebounce);

            const row = e.target.closest('.quiz-question-row');
            const questionId = row.dataset.questionId;
            const value = e.target.value;

            pointOverrideDebounce = setTimeout(() => {
                fetch(`/quizzes/${quizId}/questions/${questionId}/point-override`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        point_override: value || null
                    }),
                });
            }, 500);
        });

        new Sortable(quizQuestionList, {
            handle: '.drag-handle',
            animation: 150,
            onEnd: function() {

                const orderedIds = Array.from(quizQuestionList.querySelectorAll('.quiz-question-row'))
                    .map(row => row.dataset.questionId);

                fetch(`/quizzes/${quizId}/questions/reorder`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        question_ids: orderedIds
                    }),
                });
            },
        });

        /* ===================== QUESTION MODAL (create / edit, standalone only) ===================== */

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

            const seedOptions = JSON.parse(modal.dataset.seedOptions || '[]');
            const seedAnswers = JSON.parse(modal.dataset.seedAnswers || '[]');
            const seedFileConfig = JSON.parse(modal.dataset.seedFileConfig || '{}');

            const required = {
                typeSelect,
                optionsSection,
                answersSection,
                fileSection,
                choiceHint,
                optionsContainer,
                answersContainer,
                addOptionBtn,
                addAnswerBtn,
            };

            for (const [name, el] of Object.entries(required)) {
                if (!el) {
                    console.warn(`Question modal "${modal.id}" is missing .${name.replace(/([A-Z])/g, '-$1').toLowerCase()} — skipping init.`);
                    return;
                }
            }

            let answerRowCounter = 0;

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
                        setSectionState(answersSection, true);
                        setSectionState(fileSection, true);
                        break;
                    case 'written':
                    default:
                        break;
                }
            }

            typeSelect.addEventListener('change', refreshSections);

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

            applySeedFileConfig();
            refreshSections();
        }

        document.querySelectorAll('.question-modal').forEach(function(modal) {
            try {
                initQuestionModal(modal);
            } catch (err) {
                console.error(`Failed to initialize modal "${modal.id}":`, err);
            }
        });

    }); // closes document.addEventListener('DOMContentLoaded', ...)
</script>
@endpush