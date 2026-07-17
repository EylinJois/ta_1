@extends('layouts.app')

@section('title', 'Manage Questions - ' . $quiz->name)

@section('page-title', 'Quiz Questions')

@section('content')

<div class="mb-4">
    <h2>{{ $quiz->name }}</h2>
    <p class="text-muted mb-0">Add and order the questions this quiz will use.</p>
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

            <h5 class="mb-3">
                <i class="bi bi-list-ol"></i>
                Quiz Questions (<span id="quizQuestionCount">{{ $quiz->questions->count() }}</span>)
            </h5>

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
                        </div>

                        <div style="width: 100px;">
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                class="form-control form-control-sm point-override-input"
                                placeholder="{{ $question->point }} pt"
                                value="{{ $question->pivot->point_override }}">
                        </div>

                        <button type="button" class="icon-action-btn delete-btn remove-question-btn">
                            <i class="bi bi-trash-fill"></i>
                        </button>

                    </div>
                </div>
                @empty
                <p class="text-muted text-center py-4" id="emptyQuizQuestions">
                    No questions added yet. Use the panel on the left to add some.
                </p>
                @endforelse
            </div>

        </div>

    </div>

</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    const quizId = {{ $quiz->id }};
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const availableList = document.getElementById('availableList');
    const selectedCountEl = document.getElementById('selectedCount');
    const addSelectedBtn = document.getElementById('addSelectedBtn');
    const quizQuestionList = document.getElementById('quizQuestionList');
    const quizQuestionCount = document.getElementById('quizQuestionCount');
    const filterBank = document.getElementById('filterBank');
    const searchInput = document.getElementById('searchQuestions');

    let selectedIds = new Set();

    /* ===================== LOAD AVAILABLE QUESTIONS ===================== */

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

    searchInput.addEventListener('input', function () {
        clearTimeout(searchDebounce);
        searchDebounce = setTimeout(loadAvailable, 300);
    });

    availableList.addEventListener('change', function (e) {
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

    /* ===================== ADD SELECTED (MANUAL) ===================== */

    addSelectedBtn.addEventListener('click', function () {

        fetch(`/quizzes/${quizId}/questions`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ question_ids: Array.from(selectedIds) }),
        })
            .then(r => r.ok ? window.location.reload() : Promise.reject())
            .catch(() => alert('Failed to add questions. Please try again.'));
    });

    /* ===================== RANDOM PICK ===================== */

    document.getElementById('randomPickForm').addEventListener('submit', function (e) {
        e.preventDefault();

        const formData = new FormData(this);
        const payload = Object.fromEntries(formData.entries());

        fetch(`/quizzes/${quizId}/questions/random`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify(payload),
        })
            .then(r => r.ok ? window.location.reload() : Promise.reject())
            .catch(() => alert('Failed to add random questions. Please try again.'));
    });

    /* ===================== REMOVE ===================== */

    quizQuestionList.addEventListener('click', function (e) {

        const btn = e.target.closest('.remove-question-btn');
        if (!btn) return;

        if (!confirm('Remove this question from the quiz?')) return;

        const row = btn.closest('.quiz-question-row');
        const questionId = row.dataset.questionId;

        fetch(`/quizzes/${quizId}/questions/${questionId}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken },
        })
            .then(r => r.ok ? window.location.reload() : Promise.reject())
            .catch(() => alert('Failed to remove question. Please try again.'));
    });

    /* ===================== POINT OVERRIDE ===================== */

    let pointOverrideDebounce;

    quizQuestionList.addEventListener('input', function (e) {
        if (!e.target.classList.contains('point-override-input')) return;

        clearTimeout(pointOverrideDebounce);

        const row = e.target.closest('.quiz-question-row');
        const questionId = row.dataset.questionId;
        const value = e.target.value;

        pointOverrideDebounce = setTimeout(() => {
            fetch(`/quizzes/${quizId}/questions/${questionId}/point-override`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ point_override: value || null }),
            });
        }, 500);
    });

    /* ===================== REORDER (DRAG & DROP) ===================== */

    new Sortable(quizQuestionList, {
        handle: '.drag-handle',
        animation: 150,
        onEnd: function () {

            const orderedIds = Array.from(quizQuestionList.querySelectorAll('.quiz-question-row'))
                .map(row => row.dataset.questionId);

            fetch(`/quizzes/${quizId}/questions/reorder`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ question_ids: orderedIds }),
            });
        },
    });

});
</script>
@endpush