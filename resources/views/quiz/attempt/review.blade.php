@extends('layouts.app')

@section('title', 'Grade Attempt')

@section('page-title', 'Grade Attempt')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/qbank.css') }}">
<link rel="stylesheet" href="{{ asset('css/quiz.css') }}">
@endpush

@section('content')

<div class="mb-4">
    <a href="{{ back()->getTargetUrl()}}" class="btn btn-back">
        <i class="bi bi-arrow-left"></i>
    </a>
</div>

<div class="question-bank-banner mb-4">
    <div>
        <span class="badge bg-light text-dark mb-3">Grading</span>
        <h2>{{ $attempt->user->name }}</h2>
        <p class="mb-0">
            {{ $quiz->name }} — Attempt #{{ $attempt->attempt_no }}
        </p>
    </div>
</div>

<form action="{{ route('attempts.grade.save', $attempt) }}" method="POST">
    @csrf

    <div class="table-card mb-3">

        @foreach($orderedQuestions as $index => $question)

        @php
        $answer = $answers->get($question->quiz_question_id);
        $maxPoint = rtrim(rtrim(number_format($question->effective_point, 2), '0'), '.');
        @endphp

        <div class="mb-5 pb-4 {{ !$loop->last ? 'border-bottom' : '' }}">

            <div class="d-flex justify-content-between align-items-start mb-3">
                <span class="badge bg-light text-dark">Question {{ $index + 1 }} of {{ $orderedQuestions->count() }}</span>
                <span class="badge bg-warning text-dark">{{ ucfirst(str_replace('_', ' ', $question->type)) }}</span>
            </div>

            <h5 class="mb-3">{{ $question->question }}</h5>

            {{-- READ-ONLY ANSWER DISPLAY --}}
            <div class="readonly-answer-block mb-3">

                @if($question->type === 'choice')
                @foreach($question->options as $option)
                <div class="readonly-option-row
                        {{ $answer && $answer->select_option_id === $option->id ? 'was-selected' : '' }}
                        {{ $option->score_percent == 100 ? 'is-correct' : '' }}">
                    {{ $option->text }}
                    @if($answer && $answer->select_option_id === $option->id)
                    <span class="badge bg-secondary ms-1">Student's answer</span>
                    @endif
                    @if($option->score_percent == 100)
                    <span class="badge bg-success ms-1">Correct</span>
                    @elseif($option->score_percent > 0)
                    <span class="badge bg-success ms-1">Partially correct</span>
                    @endif
                </div>
                @endforeach

                @elseif($question->type === 'multiple_select')
                @php $selectedIds = $answer ? $answer->selectedOptions->pluck('id')->toArray() : []; @endphp
                @foreach($question->options as $option)
                <div class="readonly-option-row
                        {{ in_array($option->id, $selectedIds) ? 'was-selected' : '' }}
                        {{ $option->score_percent == 100 || in_array($option->id, $selectedIds) ? 'is-correct' : '' }}">
                    {{ $option->text }}
                    @if(in_array($option->id, $selectedIds))
                    <span class="badge bg-secondary ms-1">Selected</span>
                    @endif
                    @if($option->score_percent == 100)
                    <span class="badge bg-success ms-1">Correct</span>
                    @elseif($option->score_percent > 0)
                    <span class="badge bg-success ms-1">Partially correct</span>
                    @endif
                </div>
                @endforeach

                @elseif($question->type === 'short_answer')
                <p class="mb-1"><strong>Answer:</strong> {{ $answer->answer ?? '—' }}</p>
                <p class="text-muted small mb-0">
                    Accepted: {{ $question->acceptedAnswers->pluck('answer')->implode(', ') ?: '—' }}
                </p>

                @elseif(in_array($question->type, ['written', 'both']))
                @if($answer && $answer->answer)
                <div class="html-page">{!! $answer->answer !!}</div>
                @else
                <p class="text-muted mb-0">No written answer submitted.</p>
                @endif
                @endif

                @if(in_array($question->type, ['file', 'both']))
                <div class="{{ $question->type === 'both' ? 'mt-3 pt-3 border-top' : '' }}">
                    @if($answer && $answer->mediaAssets->isNotEmpty())
                    <ul class="list-unstyled mb-0">
                        @foreach($answer->mediaAssets as $file)
                        <li class="d-flex align-items-center justify-content-between border rounded-3 px-3 py-2 mb-2 bg-white">
                            <span><i class="bi bi-file-earmark-check text-success me-1"></i> {{ $file->original_name }}</span>
                            <a href="{{ route('media.download', $file) }}" class="btn btn-sm btn-secondary-custom">
                                <i class="bi bi-download"></i>
                            </a>
                        </li>
                        @endforeach
                    </ul>
                    @else
                    <p class="text-muted mb-0">No files uploaded.</p>
                    @endif
                </div>
                @endif

            </div>

            @if(auth()->user()->isManagerInCourse($courseId))
            {{-- SCORE INPUT (managers) --}}
            <div class="d-flex justify-content-between align-items-end flex-wrap gap-3">

                <div class="flex-grow-1" style="min-width:250px;">
                    <label class="form-label">Feedback (optional)</label>
                    <input type="text" name="feedback[{{ $question->quiz_question_id }}]" class="form-control"
                        value="{{ $answer->feedback ?? '' }}" placeholder="Notes for this question...">
                </div>

                <div>
                    <label class="form-label d-block">Score</label>
                    <div class="grade-score-box">
                        <input type="number" step="0.01" min="0" max="{{ $question->effective_point }}"
                            name="scores[{{ $question->quiz_question_id }}]" class="form-control"
                            value="{{ $answer->score ?? 0 }}">
                        <span class="grade-max-point">/ {{ $maxPoint }}</span>
                    </div>
                </div>

            </div>
            @else
            {{-- SCORE + FEEDBACK (students, read-only) --}}
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">

                <div class="flex-grow-1" style="min-width:250px;">
                    <label class="form-label">Feedback</label>
                    <div class="readonly-answer-block py-2">
                        {{ $answer?->feedback ?: 'No feedback for this question.' }}
                    </div>
                </div>

                <div>
                    <label class="form-label d-block">Score</label>
                    <div class="grade-score-display">
                        <span class="grade-score-value">
                            {{ !is_null($answer?->score) ? rtrim(rtrim(number_format($answer->score, 2), '0'), '.') : '—' }}
                        </span>
                        <span class="grade-max-point">/ {{ $maxPoint }}</span>
                    </div>
                </div>

            </div>
            @endif

        </div>

        @endforeach

    </div>

    @if(auth()->user()->isManagerInCourse($courseId))
    <div class="d-flex justify-content-end">
        <button type="submit" class="btn btn-theme btn-lg px-5">
            <i class="bi bi-check-circle"></i> Save Grade
        </button>
    </div>
    @else
    <div class="d-flex justify-content-end">
        <a href="{{ url()->previous() }}" class="btn btn-theme btn-lg px-5">
            <i class="bi bi-check-circle"></i>
            Finish Review
        </a>
    </div>
    @endif
</form>

@endsection