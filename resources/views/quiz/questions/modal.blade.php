@php
    $question     = $question ?? null;
    $questionBank = $questionBank ?? null;
    $quiz         = $quiz ?? null;
    $readOnly     = $readOnly ?? false;
    $isEdit       = (bool) $question;

    $modalId = $modalId ?? ($isEdit ? 'editQuestionModal' . $question->id : 'createQuestionModal');
    $action  = $isEdit ? route('questions.update', $question) : route('questions.store');
    $bankId  = $isEdit ? $question->question_bank_id : ($questionBank->id ?? null);

    $seedOptions = $isEdit
        ? $question->options->map(fn ($o) => ['text' => $o->text, 'score_percent' => $o->score_percent])->values()
        : [];
    $seedAnswers = $isEdit
        ? $question->acceptedAnswers->map(fn ($a) => [
            'answer' => $a->answer, 'ignore_case' => $a->ignore_case, 'ignore_space' => $a->ignore_space,
        ])->values()
        : [];
    if (!$isEdit && is_array(old('option_text'))) {
        $oldScores = old('score_percent', []);
        $oldScores = is_array($oldScores) ? $oldScores : [];
        $seedOptions = collect(old('option_text'))
            ->map(fn ($text, $index) => [
                'text' => $text,
                'score_percent' => $oldScores[$index] ?? 0,
            ])
            ->values();
    }
    if (!$isEdit && is_array(old('accepted_answers'))) {
        $oldIgnoreCase = old('ignore_case', []);
        $oldIgnoreSpace = old('ignore_space', []);
        $oldIgnoreCase = is_array($oldIgnoreCase) ? $oldIgnoreCase : [];
        $oldIgnoreSpace = is_array($oldIgnoreSpace) ? $oldIgnoreSpace : [];
        $seedAnswers = collect(old('accepted_answers'))
            ->map(fn ($answer, $index) => [
                'answer' => $answer,
                'ignore_case' => (bool) ($oldIgnoreCase[$index] ?? true),
                'ignore_space' => (bool) ($oldIgnoreSpace[$index] ?? true),
            ])
            ->values();
    }
    $seedFileConfig = $isEdit
        ? [
            'allowed'       => (array) ($question->file_allowed ?? []),
            'max_file_size' => $question->max_file_size,
            'max_files'     => $question->max_files,
        ]
        : [];

    $currentType = old('type', $question->type ?? 'choice');
@endphp

@if($readOnly)
<div class="modal fade"
    id="{{ $modalId }}"
    tabindex="-1"
    aria-labelledby="{{ $modalId }}Label"
    aria-hidden="true">

    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content custom-modal">
            <div class="modal-header">
                <h5 class="modal-title" id="{{ $modalId }}Label">
                    <i class="bi bi-eye me-2"></i>
                    View Question
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="mb-4">
                    <h6 class="form-label">Question</h6>
                    <div class="p-3 bg-light rounded text-break" style="white-space: pre-wrap;">{{ $question->question }}</div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <span class="form-label d-block">Question Type</span>
                        <span>{{ ucfirst(str_replace('_', ' ', $question->type)) }}</span>
                    </div>
                    <div class="col-md-6">
                        <span class="form-label d-block">Points</span>
                        <span>{{ $question->pivot->point_override ?? $question->point }} pt</span>
                    </div>
                </div>

                @if(in_array($question->type, ['choice', 'multiple_select'], true))
                <div class="mb-4">
                    <h6 class="form-label">Answer Options</h6>
                    <div class="d-grid gap-2">
                        @forelse($question->options->sortBy('order_index') as $option)
                        <div class="d-flex justify-content-between align-items-center gap-3 p-3 border rounded">
                            <span class="text-break">{{ $option->text }}</span>
                            <span class="badge bg-light text-dark">{{ $option->score_percent }}%</span>
                        </div>
                        @empty
                        <p class="text-muted mb-0">No answer options.</p>
                        @endforelse
                    </div>
                </div>
                @endif

                @if($question->type === 'short_answer')
                <div class="mb-4">
                    <h6 class="form-label">Accepted Answers</h6>
                    <div class="d-grid gap-2">
                        @forelse($question->acceptedAnswers as $acceptedAnswer)
                        <div class="p-3 border rounded">
                            <div class="text-break">{{ $acceptedAnswer->answer }}</div>
                            <small class="text-muted">
                                {{ $acceptedAnswer->ignore_case ? 'Ignore case' : 'Case-sensitive' }}
                                &middot;
                                {{ $acceptedAnswer->ignore_space ? 'Ignore spaces' : 'Spaces matter' }}
                            </small>
                        </div>
                        @empty
                        <p class="text-muted mb-0">No accepted answers.</p>
                        @endforelse
                    </div>
                </div>
                @endif

                @if(in_array($question->type, ['file', 'both'], true))
                <div>
                    <h6 class="form-label">File Upload Settings</h6>
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Allowed file types</dt>
                        <dd class="col-sm-8">
                            {{ $question->file_allowed ? implode(', ', $question->file_allowed) : 'Any file type' }}
                        </dd>
                        <dt class="col-sm-4">Maximum file size</dt>
                        <dd class="col-sm-8">{{ $question->max_file_size ? $question->max_file_size . ' MB' : 'Default' }}</dd>
                        <dt class="col-sm-4">Maximum number of files</dt>
                        <dd class="col-sm-8">{{ $question->max_files ?? 'Default' }}</dd>
                    </dl>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@else
<div class="modal fade question-modal"
    id="{{ $modalId }}"
    tabindex="-1"
    aria-labelledby="{{ $modalId }}Label"
    aria-hidden="true"
    data-has-validation-errors="{{ !$isEdit && old('question') !== null && $errors->any() ? '1' : '0' }}"
    data-seed-options="{{ json_encode($seedOptions) }}"
    data-seed-answers="{{ json_encode($seedAnswers) }}"
    data-seed-file-config="{{ json_encode($seedFileConfig) }}">

    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content custom-modal">

            <form action="{{ $action }}" method="POST">
                @csrf
                @if($isEdit) @method('PUT') @endif

                @if(!$isEdit && $bankId)
                <input type="hidden" name="question_bank_id" value="{{ $bankId }}">
                @endif
                @if(!$isEdit && $quiz)
                <input type="hidden" name="quiz_id" value="{{ $quiz->id }}">
                @endif

                <div class="modal-header">
                    <h5 class="modal-title" id="{{ $modalId }}Label">
                        <i class="bi {{ $isEdit ? 'bi-pencil-square' : 'bi-plus-circle' }} me-2"></i>
                        {{ $isEdit ? 'Edit Question' : 'Add Question' }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    @if(!$isEdit && old('question') !== null && $errors->any())
                    <div class="alert alert-danger" role="alert">
                        <strong>The question could not be saved:</strong>
                        <ul class="mb-0 mt-2">
                            @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    <div class="mb-4">
                        <label class="form-label">Question</label>
                        <textarea name="question" class="form-control" rows="5"
                            placeholder="Type your question..." required>{{ old('question', $question->question ?? '') }}</textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label">Question Type</label>
                            <select class="form-select question-type-select" name="type">
                                @foreach([
                                    'choice' => 'Multiple Choice',
                                    'multiple_select' => 'Multiple Select',
                                    'written' => 'Written',
                                    'short_answer' => 'Short Answer',
                                    'file' => 'File Upload',
                                    'both' => 'Written + File',
                                ] as $value => $label)
                                <option value="{{ $value }}" @selected($currentType === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 mb-4">
                            <label class="form-label">Points</label>
                            <input type="number" step="0.01" min="0" name="point" class="form-control"
                                placeholder="Enter points..." value="{{ old('point', $question->point ?? '') }}">
                        </div>
                    </div>

                    <div>
                        {{-- MULTIPLE CHOICE / MULTIPLE SELECT --}}
                        <div class="options-section d-none">
                            <label class="form-label d-flex justify-content-between align-items-center">
                                <span>Answer Options</span>
                                <span class="choice-hint text-muted small d-none">
                                    Give the correct option 100%. Options left at 0% earn nothing. The total of all options must stay between -100% and 100%.
                                </span>
                            </label>

                            <div class="options-container"></div>
                            <div class="score-total small text-muted mt-2">Total: 0%</div>

                            <button type="button" class="add-option-btn btn btn-secondary-custom btn-sm mt-2">
                                <i class="bi bi-plus-circle me-1"></i> Add Option
                            </button>
                        </div>

                        {{-- SHORT ANSWER --}}
                        <div class="answers-section d-none">
                            <label class="form-label">Accepted Answers</label>
                            <div class="answers-container"></div>
                            <button type="button" class="add-answer-btn btn btn-secondary-custom btn-sm">
                                <i class="bi bi-plus-circle me-1"></i> Add Accepted Answer
                            </button>
                        </div>

                        {{-- FILE UPLOAD --}}
                        <div class="file-section d-none">
                            <label class="form-label">Allowed File Types</label>

                            <div class="file-type-checkboxes mb-3">
                                @foreach(['pdf' => 'PDF', 'doc' => 'DOC', 'docx' => 'DOCX', 'ppt' => 'PPT', 'pptx' => 'PPTX', 'jpg' => 'JPG', 'png' => 'PNG', 'zip' => 'ZIP'] as $ext => $label)
                                <div class="form-check form-check-inline">
                                    <input type="checkbox" class="form-check-input file-type-checkbox"
                                        name="file_allowed[]" value="{{ $ext }}" id="{{ $modalId }}FileType{{ $ext }}">
                                    <label class="form-check-label" for="{{ $modalId }}FileType{{ $ext }}">{{ $label }}</label>
                                </div>
                                @endforeach
                            </div>

                            <div class="mb-4">
                                <label class="form-label">
                                    Other Extensions
                                    <small class="text-muted">(comma-separated, optional — e.g. mp4, csv)</small>
                                </label>
                                <input type="text" class="form-control file-allowed-custom" placeholder="e.g. mp4, csv">
                                <input type="hidden" name="file_allowed_json" class="file-allowed-json" value="[]">
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label class="form-label">
                                        Max File Size <small class="text-muted">(MB) - Default 2 MB</small>
                                    </label>
                                    <input type="number" min="1" step="1" class="form-control"
                                        name="max_file_size" placeholder="e.g. 2">
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label class="form-label">Max Number of Files - Default 1 File</label>
                                    <input type="number" min="1" max="255" step="1" class="form-control"
                                        name="max_files" placeholder="e.g. 1">
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary-custom" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-theme">
                        <i class="bi bi-check-circle me-1"></i>
                        {{ $isEdit ? 'Update Question' : 'Save Question' }}
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

{{-- Load the script once per page, no matter how many modals are included --}}
@once
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {

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
            const scoreTotal = modal.querySelector('.score-total');

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

            function initModal() {
                setSectionState(optionsSection, true);
                choiceHint.classList.remove('d-none');
            }

            typeSelect.addEventListener('change', refreshSections);
            initModal();

            /* ========== QUESTION OPTIONS ========== */

            function scoreInputs() {
                return Array.from(optionsContainer.querySelectorAll('input[name="score_percent[]"]'));
            }

            function scoreValue(input) {
                const v = parseInt(input.value, 10);
                return isNaN(v) ? 0 : v;
            }

            function updateScoreTotal() {
                const total = scoreInputs().reduce((sum, i) => sum + scoreValue(i), 0);

                if (scoreTotal) {
                    scoreTotal.textContent = 'Total: ' + total + '%';
                    scoreTotal.classList.toggle('text-danger', total > 100 || total < -100);
                    scoreTotal.classList.toggle('text-muted', total <= 100 && total >= -100);
                }

                return total;
            }

            function createOption(text = '', scorePercent = 0) {

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
                        <input type="number" class="form-control"
                            name="score_percent[]" value="${scorePercent}" min="-100" max="100" step="1">
                        <label class="form-check-label">Score (%)</label>
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
                updateScoreTotal();
            }

            addOptionBtn.addEventListener('click', () => createOption());

            optionsContainer.addEventListener('click', function(e) {
                if (e.target.closest('.remove-option')) {
                    if (optionsContainer.querySelectorAll('.option-row').length <= 2) return;
                    e.target.closest('.option-row').remove();
                    updateScoreTotal();
                }
            });

            optionsContainer.addEventListener('input', function(e) {
                const input = e.target;
                if (!input.matches('input[name="score_percent[]"]')) return;

                // empty (or a lone "-" while typing): leave it alone
                if (input.value === '' || input.value === '-') {
                    updateScoreTotal();
                    return;
                }

                const othersSum = scoreInputs()
                    .filter(i => i !== input)
                    .reduce((sum, i) => sum + scoreValue(i), 0);

                // keep this option within -100..100 AND the overall total within -100..100
                const lower = Math.max(-100, -100 - othersSum);
                const upper = Math.min(100, 100 - othersSum);

                const clamped = Math.max(lower, Math.min(upper, scoreValue(input)));
                if (String(clamped) !== input.value) input.value = clamped;

                updateScoreTotal();
            });

            // don't leave a blank score behind when the user tabs away
            optionsContainer.addEventListener('focusout', function(e) {
                if (e.target.matches('input[name="score_percent[]"]') && e.target.value === '') {
                    e.target.value = 0;
                    updateScoreTotal();
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
                seedOptions.forEach(o => createOption(o.text, o.score_percent));
            } else {
                createOption();
                createOption();
            }

            if (seedAnswers.length) {
                seedAnswers.forEach(a => createAnswer(a.answer, !!a.ignore_case, !!a.ignore_space));
            } else {
                createAnswer();
            }

            modal.querySelector('form').addEventListener('submit', function(e) {
                if (optionsSection.classList.contains('d-none')) return;

                const total = updateScoreTotal();
                if (total > 100 || total < -100) {
                    e.preventDefault();
                    alert('The total score of all options must be between -100% and 100%.');
                }
            });

            refreshSections();
        }
        document.querySelectorAll('.question-modal').forEach(initQuestionModal);

        document.querySelectorAll('.question-modal[data-has-validation-errors="1"]').forEach(function(modal) {
            bootstrap.Modal.getOrCreateInstance(modal).show();
        });
    });
</script>
@endpush
@endonce
@endif
