@extends('layouts.app')

@section('title','Create Quiz')

@section('page-title','Create Quiz')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/qbank.css') }}">
@endpush

@section('content')

<div class="mb-4">
    <a href="{{ route('sections.show',$section) }}"
        class="btn btn-back">
        <i class="bi bi-arrow-left"></i>
    </a>
</div>

<div class="quiz-header mb-4">

    <div>

        <span class="quiz-badge">

            {{ $course->name }}

        </span>

        <h2>Create Quiz</h2>

        <p>

            Configure the quiz settings before adding questions.

        </p>

    </div>


</div>

<form action="{{ route('quizzes.store') }}"
    method="POST">

    @csrf

    <input type="hidden"
        name="section_id"
        value="{{ $section->id }}">

    <div class="table-card">

        <h4 class="mb-4">
            Quiz Information
        </h4>

        <div class="mb-4">

            <label class="form-label">

                Quiz Name

            </label>

            <input
                class="form-control"
                name="name"
                required>

        </div>

        <div class="mb-4">

            <label class="form-label">

                Description

            </label>

            <textarea
                class="form-control"
                rows="5"
                name="description"></textarea>

        </div>

        <div class="row">

            <div class="col-md-6 mb-4">

                <label class="form-label">

                    Visibility

                </label>

                <select
                    class="form-select"
                    name="visibility">

                    <option value="0">

                        Hidden

                    </option>

                    <option value="1">

                        Visible

                    </option>

                </select>

            </div>

            <div class="col-md-6 mb-4">

                <label class="form-label">

                    Maximum Attempts

                </label>

                <input
                    type="number"
                    min="1"
                    class="form-control"
                    name="max_attempt">

            </div>

        </div>

        <div class="row">
            <div class="col-md-6 mb-4">
                <label class="form-label">
                    Duration (minutes)
                </label>
                <input
                    type="number"
                    min="1"
                    class="form-control"
                    name="duration">
                <small class="text-muted">
                    Leave empty for unlimited time.
                </small>
            </div>
            <div class="col-md-6 mb-4">
                <label class="form-label">
                    Passing Score (%)
                </label>
                <input
                    type="number"
                    class="form-control"
                    name="pass_score"
                    min="0"
                    max="100"
                    step="0.01">
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-4">
                <label class="form-label">
                    Opened at
                </label>
                <input
                    type="date"
                    class="form-control"
                    name="opened_at">
                <small class="text-muted">
                    Leave empty to open instantly after creation.
                </small>
            </div>
            <div class="col-md-6 mb-4">
                <label class="form-label">
                    Due at
                </label>
                <input
                    type="date"
                    class="form-control"
                    name="due_at">
                <small class="text-muted">
                    Leave empty for no due date. Must be after opened at.
                </small>
            </div>
        </div>

        <div class="row">

            <div class="col-md-6">

                <div class="form-check form-switch">

                    <input
                        class="form-check-input"
                        type="checkbox"
                        checked
                        value="1"
                        name="shuffle_questions">

                    <label class="form-check-label">

                        Shuffle Questions

                    </label>

                </div>

            </div>

            <div class="col-md-6">

                <div class="form-check form-switch">

                    <input
                        class="form-check-input"
                        type="checkbox"
                        checked
                        value="1"
                        name="shuffle_options">

                    <label class="form-check-label">

                        Shuffle Answer Options

                    </label>

                </div>

            </div>

        </div>

        <hr class="my-4">

        <div class="d-flex justify-content-end gap-2">

            <a href="{{ route('sections.show',$section) }}"
                class="btn btn-secondary-custom">

                Cancel

            </a>

            <button
                class="btn btn-theme">

                <i class="bi bi-check-circle"></i>

                Create Quiz

            </button>

        </div>

    </div>

</form>

@endsection