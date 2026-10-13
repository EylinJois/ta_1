{{-- Edit Quiz Settings --}}
<div class="modal fade"
    id="editQuizModal"
    tabindex="-1"
    aria-labelledby="editQuizModalLabel"
    aria-hidden="true">

    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-fullscreen-sm-down">

        <div class="modal-content custom-modal">

            <form action="{{ route('quizzes.update', $quiz) }}" method="POST">

                @csrf
                @method('PUT')

                <div class="modal-header">

                    <h5 class="modal-title" id="editQuizModalLabel">
                        <i class="bi bi-patch-question-fill me-2"></i>
                        Edit Quiz
                    </h5>

                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>

                </div>

                <div class="modal-body">

                    <div class="mb-4">
                        <label class="form-label">Quiz Name</label>
                        <input type="text" name="name" class="form-control" maxlength="255" required
                            value="{{ $quiz->name }}">
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="4">{{ $quiz->description }}</textarea>
                    </div>

                    <div class="row">

                        <div class="col-md-6 mb-4">
                            <label class="form-label">
                                Max Attempts
                                <small class="text-muted">(optional)</small>
                            </label>
                            <input type="number" name="max_attempt" class="form-control" min="1" max="255"
                                placeholder="Unlimited" value="{{ $quiz->max_attempt }}">
                        </div>

                        <div class="col-md-6 mb-4">
                            <label class="form-label">
                                Duration
                                <small class="text-muted">(minutes, optional)</small>
                            </label>
                            <input type="number" name="duration_minutes" class="form-control" min="1"
                                placeholder="No time limit"
                                value="{{ $quiz->duration ? round($quiz->duration / 60) : '' }}">
                        </div>

                    </div>

                    <div class="row">

                        <div class="col-md-6 mb-4">
                            <label class="form-label">
                                Pass Score
                                <small class="text-muted">(%, optional)</small>
                            </label>
                            <input type="number" name="pass_score" class="form-control" step="0.01" min="0" max="100"
                                placeholder="No minimum" value="{{ $quiz->pass_score }}">
                        </div>

                        <div class="col-md-6 mb-4">
                            <label class="form-label">Visibility</label>
                            <select name="visibility" class="form-select">
                                <option value="1" @selected($quiz->visibility == 1)>Visible</option>
                                <option value="0" @selected($quiz->visibility == 0)>Hidden</option>
                            </select>
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
    name="opened_at"
    value="{{ old('opened_at', $quiz->opened_at?->format('Y-m-d')) }}">
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
                                name="due_at"
                                value="{{ old('due_at', $quiz->due_at?->format('Y-m-d')) }}">
                            <small class="text-muted">
                                Leave empty for no due date. Must be after opened at.
                            </small>
                        </div>
                    </div>
                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch">
                                <input type="hidden" name="shuffle_questions" value="0">
                                <input
                                    type="checkbox"
                                    class="form-check-input"
                                    id="shuffleQuestions"
                                    name="shuffle_questions"
                                    value="1"
                                    @checked($quiz->shuffle_questions)>
                                <label class="form-check-label" for="shuffleQuestions">
                                    Shuffle Questions
                                </label>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch">
                                <input type="hidden" name="shuffle_options" value="0">
                                <input
                                    type="checkbox"
                                    class="form-check-input"
                                    id="shuffleOptions"
                                    name="shuffle_options"
                                    value="1"
                                    @checked($quiz->shuffle_options)>
                                <label class="form-check-label" for="shuffleOptions">
                                    Shuffle Options
                                </label>
                            </div>
                        </div>

                    </div>

                </div>

                <div class="modal-footer">

                    <button type="button" class="btn btn-secondary-custom" data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit" class="btn btn-theme">
                        <i class="bi bi-check-circle me-1"></i>
                        Save Changes
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>