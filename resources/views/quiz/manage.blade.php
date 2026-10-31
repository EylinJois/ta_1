@extends('layouts.app')

@section('title', 'Manage Questions - ' . $quiz->name)

@section('page-title', 'Manage Quiz Questions')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/qbank.css') }}">
@endpush

@section('content')
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if($hasAttempts)
<div class="alert alert-info" role="status">
    This quiz has an attempt. Its questions, points, order, and other settings are locked; visibility can still be changed.
</div>
@endif

<div class="d-flex justify-content-between align-items-start mb-4">

    <div class="d-flex align-items-center gap-3">

        <a href="{{ route('sections.show', $quiz->section) }}" class="btn btn-back">
            <i class="bi bi-arrow-left"></i>
        </a>

        <div>
            <h2 class="mb-0">{{ $quiz->name }}</h2>
            <p class="text-muted mb-0">
                {{ $hasAttempts ? 'Review the questions used by this quiz.' : 'Add and order the questions this quiz will use.' }}
            </p>
        </div>

    </div>

    <button type="button"
        class="btn btn-secondary-custom"
        data-bs-toggle="modal"
        data-bs-target="#editQuizModal">
        <i class="bi bi-pencil-fill"></i>
        {{ $hasAttempts ? 'Edit Visibility' : 'Edit Quiz' }}
    </button>

</div>

<div class="row g-4">

    {{-- LEFT: pick questions --}}
    @unless($hasAttempts)
    <div class="col-lg-6">

        <div class="table-card">

            <h5 class="mb-3">
                <i class="bi bi-search"></i>
                Pick From Question Banks
            </h5>

            <div class="mb-3">
                <label class="form-label">Question Bank</label>
                <div class="qbank-code-search position-relative">
                    <input
                        type="text"
                        class="form-control"
                        id="manualBankCodeSearch"
                        placeholder="Search by code, e.g. QB1..."
                        autocomplete="off">
                    <input type="hidden" id="manualBankCodeSelectedId">
                    <div id="manualBankCodeResults" class="qbank-code-results d-none"></div>
                </div>
            </div>

            <p class="text-muted text-center py-3 mb-3" id="availableStatus">Select a question bank above to see its questions.</p>
            <div id="availableList" class="mb-3" style="max-height: 400px; overflow-y: auto;"></div>

            <template id="availableQuestionTemplate">
                <div class="form-check border-bottom py-2">
                    <input class="form-check-input available-question-checkbox" type="checkbox">
                    <label class="form-check-label d-block">
                        <span class="available-question-text"></span>
                        <span class="badge bg-light text-dark ms-1 available-question-type"></span>
                    </label>
                </div>
            </template>

            <button type="button" id="addSelectedBtn" class="btn btn-theme w-100" disabled>
                <i class="bi bi-plus-circle"></i>
                Add Selected (<span id="selectedCount">0</span>)
            </button>

        </div>

    </div>
    @endunless

    {{-- RIGHT: current quiz questions --}}
    <div class="{{ $hasAttempts ? 'col-12' : 'col-lg-6' }}">

        <div class="table-card">

            <div class="d-flex justify-content-between align-items-center mb-3">

                <h5 class="mb-0">
                    <i class="bi bi-list-ol"></i>
                    Quiz Questions (<span id="quizQuestionCount">{{ $quiz->questions->count() }}</span>)
                </h5>

                @unless($hasAttempts)
                <button type="button"
                    class="btn btn-theme btn-sm"
                    data-bs-toggle="modal"
                    data-bs-target="#createQuestionModal">
                    <i class="bi bi-plus-circle"></i>
                    Add Question
                </button>
                @endunless

            </div>

            <div id="quizQuestionList">
                @forelse($quiz->questions as $question)
                <div class="quiz-question-row card mb-2" data-question-id="{{ $question->id }}">
                    <div class="card-body d-flex align-items-center gap-3 py-2 position-relative">
                        <button type="button"
                            class="question-view-trigger"
                            data-bs-toggle="modal"
                            data-bs-target="#viewQuestionModal{{ $question->id }}"
                            aria-label="View question: {{ Str::limit($question->question, 120) }}"></button>

                        <div class="drag-handle">
                            <i class="bi bi-list"></i>
                        </div>

                        <div class="flex-grow-1">
                            <div>{{ Str::limit($question->question, 80) }}</div>
                            @if($question->type == 'both')
                            <span class="badge bg-light text-dark">
                                Written + File
                            </span>
                            @else
                            <span class="badge bg-light text-dark">
                                {{ ucfirst(str_replace('_', ' ', $question->type)) }}
                            </span>
                            @endif

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

                        @unless($hasAttempts)
                        @if(is_null($question->question_bank_id))
                        <button type="button"
                            class="icon-action-btn edit-btn"
                            data-bs-toggle="modal"
                            data-bs-target="#editQuestionModal{{ $question->id }}">
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
                        @endunless

                    </div>
                </div>

                @if(!$hasAttempts && is_null($question->question_bank_id))
                @include('quiz.questions.modal', ['question' => $question, 'modalId' => 'editQuestionModal'.$question->id])
                @endif

                @empty
                <p class="text-muted text-center py-4" id="emptyQuizQuestions">
                    No questions added yet. Use the panel on the left to add some.
                </p>
                @endforelse
            </div>

        </div>

        @foreach($quiz->questions as $question)
        @include('quiz.questions.modal', [
            'question' => $question,
            'modalId' => 'viewQuestionModal'.$question->id,
            'readOnly' => true,
        ])
        @endforeach

    </div>

</div>

@if(!$hasAttempts)
@foreach($quiz->questions->filter(fn ($q) => $q->question_bank_id) as $question)
@include('quiz.edit-point', ['quiz' => $quiz, 'question' => $question])
@endforeach
@include('quiz.questions.modal', ['modalId'=>'createQuestionModal', 'question' => null, 'questionBank' => null, 'quiz' => $quiz])
@endif
@include('quiz.edit-quiz')

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (@json($hasAttempts)) return;

        /* ===================== QUIZ QUESTION MANAGEMENT ===================== */

        const quizId = {{$quiz->id}};
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        const availableList = document.getElementById('availableList');
        const selectedCountEl = document.getElementById('selectedCount');
        const addSelectedBtn = document.getElementById('addSelectedBtn');
        const quizQuestionList = document.getElementById('quizQuestionList');
        const availableStatus = document.getElementById('availableStatus');
        const availableQuestionTemplate = document.getElementById('availableQuestionTemplate');

        let selectedIds = new Set();

        function showAvailableStatus(message) {
            availableList.replaceChildren();
            availableStatus.textContent = message;
            availableStatus.classList.remove('d-none');
        }

        function loadAvailable() {
            const manualBankId = document.getElementById('manualBankCodeSelectedId')?.value;
            if (!manualBankId) {
                showAvailableStatus('Select a question bank above to see its questions.');
                return;
            }

            showAvailableStatus('Loading questions...');

            const params = new URLSearchParams();
            params.set('question_bank_id', manualBankId);

            fetch(`/quizzes/${quizId}/questions/available?${params.toString()}`)
                .then(r => r.json())
                .then(questions => {
                    if (questions.length === 0) {
                        showAvailableStatus('No matching questions.');
                        return;
                    }

                    const rows = document.createDocumentFragment();
                    questions.forEach(function(q) {
                        const row = availableQuestionTemplate.content.firstElementChild.cloneNode(true);
                        const checkbox = row.querySelector('.available-question-checkbox');
                        const questionId = `q${q.id}`;

                        checkbox.value = q.id;
                        checkbox.id = questionId;
                        checkbox.checked = selectedIds.has(Number(q.id));
                        row.querySelector('label').htmlFor = questionId;
                        row.querySelector('.available-question-text').textContent = String(q.question).slice(0, 100);
                        row.querySelector('.available-question-type').textContent = String(q.type).replace('_', ' ');
                        rows.appendChild(row);
                    });

                    availableList.replaceChildren(rows);
                    availableStatus.classList.add('d-none');
                })
                .catch(function() {
                    showAvailableStatus('Could not load questions. Please try again.');
                });
        }

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

        quizQuestionList.addEventListener('click', function(e) {
            const btn = e.target.closest('.remove-question-btn');
            if (!btn || btn.disabled) return;

            if (!confirm('Remove this question from the quiz?')) return;

            btn.disabled = true;

            const row = btn.closest('.quiz-question-row');
            const questionId = row.dataset.questionId;

            fetch(`/quizzes/${quizId}/questions/${questionId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                })
                .then(r => r.ok ? window.location.reload() : Promise.reject())
                .catch(() => {
                    btn.disabled = false;
                    alert('Failed to remove question. Please try again.');
                });
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

        /* ===================== BANK CODE SEARCH (Pick Manually panel only) ===================== */

        const questionBanksData = [
            @foreach($questionBanks as $bank)
            {
                id: @json($bank->id),
                code: @json('QB' . $bank->id),
                name: @json($bank->name)
            },
            @endforeach
        ];

        function setupBankCodeSearch(searchInputId, selectedIdInputId, resultsId, onSelect) {

            const searchInput = document.getElementById(searchInputId);
            const selectedIdInput = document.getElementById(selectedIdInputId);
            const resultsContainer = document.getElementById(resultsId);

            if (!searchInput || !selectedIdInput || !resultsContainer) return;

            function render(query) {

                const normalizedQuery = query.trim().toLowerCase();

                if (!normalizedQuery) {
                    resultsContainer.classList.add('d-none');
                    resultsContainer.innerHTML = '';
                    return;
                }

                const matches = questionBanksData.filter(function(bank) {
                    return bank.code.toLowerCase().includes(normalizedQuery) ||
                        bank.name.toLowerCase().includes(normalizedQuery);
                });

                if (matches.length === 0) {
                    resultsContainer.innerHTML = '<div class="qbank-code-no-match">No matching question bank.</div>';
                    resultsContainer.classList.remove('d-none');
                    return;
                }

                resultsContainer.innerHTML = matches.map(function(bank) {
                    return `
                    <div class="qbank-code-result-item" data-id="${bank.id}" data-code="${bank.code}" data-name="${bank.name.replace(/"/g, '&quot;')}">
                        <span class="code">${bank.code}</span> (${bank.name})
                    </div>
                `;
                }).join('');

                resultsContainer.classList.remove('d-none');
            }

            searchInput.addEventListener('input', function() {
                selectedIdInput.value = '';
                render(this.value);
            });

            searchInput.addEventListener('focus', function() {
                if (this.value.trim()) render(this.value);
            });

            resultsContainer.addEventListener('click', function(e) {
                const item = e.target.closest('.qbank-code-result-item');
                if (!item) return;

                selectedIdInput.value = item.dataset.id;
                searchInput.value = `${item.dataset.code} (${item.dataset.name})`;
                resultsContainer.classList.add('d-none');

                if (onSelect) onSelect(item.dataset.id);
            });

            document.addEventListener('click', function(e) {
                if (!e.target.closest(`#${searchInputId}`) && !e.target.closest(`#${resultsId}`)) {
                    resultsContainer.classList.add('d-none');
                }
            });
        }

        setupBankCodeSearch('manualBankCodeSearch', 'manualBankCodeSelectedId', 'manualBankCodeResults', function() {
            loadAvailable();
        });

    });
</script>
@endpush