@extends('layouts.app')

@section('title', 'Taking Quiz - ' . $quiz->name)

@section('page-title', 'Quiz Attempt')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/qbank.css') }}">
<link rel="stylesheet" href="{{ asset('css/quiz.css') }}">
<style>
    
</style>
@endpush

@section('content')

<div class="attempt-layout">

    {{-- SIDEBAR --}}
    <div class="attempt-sidebar">

        @if(!is_null($remainingSeconds))
        <div class="timer-box" id="timerBox">
            <div class="text-muted small mb-1">Time Remaining</div>
            <div class="timer-value" id="timerValue">--:--:--</div>
        </div>
        @endif

        <div class="table-card">

            <h6 class="mb-3">Questions</h6>

            <div class="question-nav-grid" id="questionNavGrid">
                @foreach($orderedQuestions as $index => $question)
                <button
                    type="button"
                    class="question-nav-btn {{ $index === 0 ? 'current' : '' }} {{ isset($existingAnswers[$question->quiz_question_id]) ? 'answered' : '' }}"
                    data-index="{{ $index }}"
                    id="navBtn{{ $index }}">
                    {{ $index + 1 }}
                </button>
                @endforeach
            </div>

            <div class="mt-3 small text-muted">
                <span class="d-inline-block me-2">
                    <span class="d-inline-block" style="width:10px;height:10px;background:rgba(214,40,40,.3);border-radius:3px;"></span>
                    Answered
                </span>
            </div>

        </div>

    </div>

    {{-- MAIN --}}
    <div class="attempt-main">

        <div class="table-card mb-3 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">{{ $quiz->name }}</h5>
                <span class="autosave-indicator" id="autosaveIndicator">All changes saved</span>
            </div>
            <button type="button" class="btn btn-theme" id="submitQuizBtn" data-bs-toggle="modal" data-bs-target="#confirmSubmitModal">
                <i class="bi bi-check-circle"></i>
                Submit Quiz
            </button>
        </div>

        <div class="table-card">

            @foreach($orderedQuestions as $index => $question)

            @php
            $existing = $existingAnswers[$question->quiz_question_id] ?? null;
            @endphp

            <div class="attempt-question-panel {{ $index === 0 ? 'active' : '' }}"
                data-index="{{ $index }}"
                data-quiz-question-id="{{ $question->quiz_question_id }}"
                data-type="{{ $question->type }}">

                <div class="d-flex justify-content-between align-items-start mb-3">
                    <span class="badge bg-light text-dark">Question {{ $index + 1 }} of {{ $orderedQuestions->count() }}</span>
                    <span class="badge bg-warning text-dark">{{ rtrim(rtrim(number_format($question->effective_point, 2), '0'), '.') }} pts</span>
                </div>

                <h5 class="mb-4">{{ $question->question }}</h5>

                {{-- CHOICE --}}
                @if($question->type === 'choice')
                <div class="options-list">
                    @foreach($question->options as $option)
                    <div class="option-row-select {{ $existing && $existing->select_option_id === $option->id ? 'selected' : '' }}"
                        data-option-id="{{ $option->id }}">
                        <input type="radio" name="q{{ $question->quiz_question_id }}_choice" class="form-check-input me-2"
                            value="{{ $option->id }}" {{ $existing && $existing->select_option_id === $option->id ? 'checked' : '' }}>
                        {{ $option->text }}
                    </div>
                    @endforeach
                </div>
                @endif

                {{-- MULTIPLE SELECT --}}
                @if($question->type === 'multiple_select')
                @php $selectedOptionIds = $existing ? $existing->selectedOptions->pluck('id')->toArray() : []; @endphp
                <div class="options-list">
                    @foreach($question->options as $option)
                    <div class="option-row-select {{ in_array($option->id, $selectedOptionIds) ? 'selected' : '' }}"
                        data-option-id="{{ $option->id }}">
                        <input type="checkbox" class="form-check-input me-2"
                            value="{{ $option->id }}" {{ in_array($option->id, $selectedOptionIds) ? 'checked' : '' }}>
                        {{ $option->text }}
                    </div>
                    @endforeach
                </div>
                @endif

                {{-- SHORT ANSWER --}}
                @if($question->type === 'short_answer')
                <input type="text" class="form-control short-answer-input" placeholder="Type your answer..."
                    value="{{ $existing->answer ?? '' }}">
                @endif

                {{-- WRITTEN / BOTH: rich text --}}
                @if(in_array($question->type, ['written', 'both']))
                <textarea class="form-control tinymce-editor" rows="8">{!! $existing->answer ?? '' !!}</textarea>
                @endif

                {{-- FILE / BOTH: multiple upload --}}
                @if(in_array($question->type, ['file', 'both']))
                @php
                $maxFiles = $question->max_files ?? 1;
                $existingFiles = $existing ? $existing->mediaAssets : collect();
                @endphp
                <div class="mt-4">
                    <label class="form-label">
                        Upload {{ $maxFiles > 1 ? 'Files' : 'File' }}
                        <small class="text-muted">
                            (max {{ $maxFiles }} {{ Str::plural('file', $maxFiles) }}
                            @if($question->max_file_size), {{ $question->max_file_size }}MB each @endif)
                        </small>
                    </label>

                    <input type="file" class="form-control file-answer-input"
                        @if($question->file_allowed) accept="{{ collect($question->file_allowed)->map(fn($e) => '.'.$e)->implode(',') }}" @endif>

                    <ul class="list-unstyled mt-2 mb-0" id="fileList{{ $index }}">
                        @foreach($existingFiles as $file)
                        <li class="d-flex align-items-center justify-content-between border rounded-3 px-3 py-2 mb-2" data-media-id="{{ $file->id }}">
                            <span><i class="bi bi-file-earmark-check text-success me-1"></i> {{ $file->original_name }}</span>
                            <button type="button" class="btn btn-sm btn-outline-danger remove-file-btn" data-media-id="{{ $file->id }}">
                                <i class="bi bi-trash"></i>
                            </button>
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif

            </div>

            @endforeach

            <div class="d-flex justify-content-between mt-4 pt-3 border-top">
                <button type="button" class="btn btn-secondary-custom" id="prevBtn" disabled>
                    <i class="bi bi-arrow-left"></i>
                    Previous
                </button>
                <button type="button" class="btn btn-theme" id="nextBtn">
                    Next
                    <i class="bi bi-arrow-right"></i>
                </button>
            </div>

        </div>

    </div>

</div>

{{-- CONFIRM SUBMIT MODAL --}}
<div class="modal fade" id="confirmSubmitModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content custom-modal">
            <div class="modal-header">
                <h5 class="modal-title">Submit Quiz?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>
                    You've answered <span id="answeredCountText">0</span> of {{ $orderedQuestions->count() }} questions.
                    Once submitted, you won't be able to change your answers.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary-custom" data-bs-dismiss="modal">Keep Working</button>
                <form action="{{ route('attempts.submit', $attempt) }}" method="POST" id="finalSubmitForm">
                    @csrf
                    <button type="submit" class="btn btn-theme">Submit Now</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    const attemptId = {{ $attempt->id }};
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const remainingSecondsInitial = @json($remainingSeconds);

    const panels = Array.from(document.querySelectorAll('.attempt-question-panel'));
    const navButtons = Array.from(document.querySelectorAll('.question-nav-btn'));
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const autosaveIndicator = document.getElementById('autosaveIndicator');
    const answeredCountText = document.getElementById('answeredCountText');

    let currentIndex = 0;

    /* ===================== TINYMCE for written/both ===================== */

    tinymce.init({
        selector: '.tinymce-editor',
        height: 250,
        menubar: true,
        plugins: 'lists link',
        toolbar: 'bold italic underline | bullist numlist | link',
        setup: function (editor) {
            editor.on('change keyup', function () {
                editor.save();
                scheduleAutosave(editor.getElement().closest('.attempt-question-panel'));
            });
        },
    });

    /* ===================== NAVIGATION ===================== */

    function showPanel(index) {
        panels.forEach(function (p) {
            p.classList.toggle('active', parseInt(p.dataset.index) === index);
        });
        navButtons.forEach(function (b) {
            b.classList.toggle('current', parseInt(b.dataset.index) === index);
        });
        prevBtn.disabled = index === 0;
        if (index === panels.length - 1) {
            nextBtn.innerHTML = 'Finish <i class="bi bi-flag"></i>';
        } else {
            nextBtn.innerHTML = 'Next <i class="bi bi-arrow-right"></i>';
        }
        currentIndex = index;
    }

    navButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            showPanel(parseInt(btn.dataset.index));
        });
    });

    prevBtn.addEventListener('click', function () {
        if (currentIndex > 0) {
            showPanel(currentIndex - 1);
        }
    });

    nextBtn.addEventListener('click', function () {
        if (currentIndex < panels.length - 1) {
            showPanel(currentIndex + 1);
        } else {
            new bootstrap.Modal(document.getElementById('confirmSubmitModal')).show();
        }
    });

    /* ===================== OPTION SELECTION (choice / multiple_select) ===================== */

    panels.forEach(function (panel) {

        const type = panel.dataset.type;

        if (type === 'choice') {
            panel.querySelectorAll('.option-row-select').forEach(function (row) {
                row.addEventListener('click', function () {
                    panel.querySelectorAll('.option-row-select').forEach(function (r) {
                        r.classList.remove('selected');
                        r.querySelector('input').checked = false;
                    });
                    row.classList.add('selected');
                    row.querySelector('input').checked = true;
                    markAnswered(panel);
                    scheduleAutosave(panel);
                });
            });
        }

        if (type === 'multiple_select') {
            panel.querySelectorAll('.option-row-select').forEach(function (row) {
                row.addEventListener('click', function (e) {
                    if (e.target.tagName !== 'INPUT') {
                        row.querySelector('input').checked = !row.querySelector('input').checked;
                    }
                    row.classList.toggle('selected', row.querySelector('input').checked);
                    markAnswered(panel);
                    scheduleAutosave(panel);
                });
            });
        }

        const shortAnswerInput = panel.querySelector('.short-answer-input');
        if (shortAnswerInput) {
            shortAnswerInput.addEventListener('input', function () {
                markAnswered(panel);
                scheduleAutosave(panel);
            });
        }

        const fileInput = panel.querySelector('.file-answer-input');
        if (fileInput) {
            fileInput.addEventListener('change', function () {
                uploadFile(panel);
            });
        }

    });

    function markAnswered(panel) {
        const index = parseInt(panel.dataset.index);
        navButtons[index].classList.add('answered');
        updateAnsweredCount();
    }

    function updateAnsweredCount() {
        answeredCountText.textContent = document.querySelectorAll('.question-nav-btn.answered').length;
    }
    updateAnsweredCount();

    /* ===================== AUTOSAVE ===================== */

    const autosaveTimers = {};

    function scheduleAutosave(panel) {
        const quizQuestionId = panel.dataset.quizQuestionId;
        clearTimeout(autosaveTimers[quizQuestionId]);
        autosaveTimers[quizQuestionId] = setTimeout(function () {
            saveAnswer(panel);
        }, 800);
    }

    function collectPayload(panel) {
        const type = panel.dataset.type;
        const payload = { quiz_question_id: panel.dataset.quizQuestionId };

        if (type === 'choice') {
            const checked = panel.querySelector('input[type="radio"]:checked');
            payload.select_option_id = checked ? checked.value : null;
        }

        if (type === 'multiple_select') {
            payload.option_ids = Array.from(
                panel.querySelectorAll('input[type="checkbox"]:checked')
            ).map(function (cb) { return cb.value; });
        }

        if (type === 'short_answer') {
            const el = panel.querySelector('.short-answer-input');
            payload.answer = el ? el.value : '';
        }

        if (type === 'written' || type === 'both') {
            const el = panel.querySelector('.tinymce-editor');
            payload.answer = el ? el.value : '';
        }

        return payload;
    }

    function saveAnswer(panel) {
        const type = panel.dataset.type;
        if (type === 'file') {
            return;
        }

        autosaveIndicator.textContent = 'Saving...';
        autosaveIndicator.className = 'autosave-indicator saving';

        fetch('/attempts/' + attemptId + '/answer', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(collectPayload(panel)),
        })
            .then(function (r) {
                return r.ok ? r.json() : Promise.reject();
            })
            .then(function () {
                autosaveIndicator.textContent = 'All changes saved';
                autosaveIndicator.className = 'autosave-indicator saved';
            })
            .catch(function () {
                autosaveIndicator.textContent = 'Failed to save — retrying...';
                autosaveIndicator.className = 'autosave-indicator';
            });
    }

    function uploadFile(panel) {
        const fileInput = panel.querySelector('.file-answer-input');
        const file = fileInput.files[0];
        if (!file) {
            return;
        }

        const formData = new FormData();
        formData.append('quiz_question_id', panel.dataset.quizQuestionId);
        formData.append('file', file);

        const listEl = panel.querySelector('[id^="fileList"]');

        fetch('/attempts/' + attemptId + '/answer/file', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            body: formData,
        })
            .then(async function (r) {
                const body = await r.json();
                if (!r.ok) {
                    throw new Error(body.message || 'Upload failed');
                }

                const li = document.createElement('li');
                li.className = 'd-flex align-items-center justify-content-between border rounded-3 px-3 py-2 mb-2';
                li.dataset.mediaId = body.media_asset_id;
                li.innerHTML =
                    '<span><i class="bi bi-file-earmark-check text-success me-1"></i> ' + body.file_name + '</span>' +
                    '<button type="button" class="btn btn-sm btn-outline-danger remove-file-btn" data-media-id="' + body.media_asset_id + '">' +
                    '<i class="bi bi-trash"></i>' +
                    '</button>';
                listEl.appendChild(li);

                markAnswered(panel);
                fileInput.value = '';
            })
            .catch(function (err) {
                alert(err.message);
                fileInput.value = '';
            });
    }

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.remove-file-btn');
        if (!btn) {
            return;
        }

        const mediaId = btn.dataset.mediaId;

        fetch('/attempts/' + attemptId + '/answer/file/' + mediaId, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken },
        })
            .then(function (r) {
                if (r.ok) {
                    btn.closest('li').remove();
                }
            });
    });

    // Periodic safety-net autosave of the currently visible question.
    setInterval(function () {
        const activePanel = panels[currentIndex];
        if (activePanel && activePanel.dataset.type !== 'file') {
            saveAnswer(activePanel);
        }
    }, 20000);

    /* ===================== TIMER ===================== */

    if (remainingSecondsInitial !== null) {

        let remaining = remainingSecondsInitial;
        const timerValue = document.getElementById('timerValue');
        const timerBox = document.getElementById('timerBox');

        function formatTime(seconds) {
            const h = Math.floor(seconds / 3600);
            const m = Math.floor((seconds % 3600) / 60);
            const s = seconds % 60;
            return [h, m, s].map(function (v) {
                return String(v).padStart(2, '0');
            }).join(':');
        }

        timerValue.textContent = formatTime(remaining);

        const timerInterval = setInterval(function () {
            remaining--;

            if (remaining <= 0) {
                clearInterval(timerInterval);
                timerValue.textContent = '00:00:00';
                autosaveIndicator.textContent = 'Time is up — submitting...';
                document.getElementById('finalSubmitForm').submit();
                return;
            }

            if (remaining <= 60) {
                timerBox.classList.add('warning');
            }

            timerValue.textContent = formatTime(remaining);
        }, 1000);
    }

});
</script>
@endpush