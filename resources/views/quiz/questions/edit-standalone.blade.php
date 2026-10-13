{{-- Edit Question (quiz-only, not tied to a bank) --}}
<div class="modal fade question-modal"
    id="editStandaloneQuestionModal{{ $question->id }}"
    tabindex="-1"
    aria-labelledby="editStandaloneQuestionModalLabel{{ $question->id }}"
    aria-hidden="true"
    data-seed-options="{{ $question->options->map(fn($o) => ['text' => $o->text, 'is_correct' => $o->is_correct])->toJson() }}"
    data-seed-answers="{{ $question->acceptedAnswers->map(fn($a) => ['answer' => $a->answer, 'ignore_case' => $a->ignore_case, 'ignore_space' => $a->ignore_space])->toJson() }}"
    data-seed-file-config="{{ json_encode([
        'allowed' => $question->file_allowed ?? [],
        'max_file_size' => $question->max_file_size,
        'max_files' => $question->max_files,
    ]) }}">

    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-fullscreen-sm-down">

        <div class="modal-content custom-modal">

            <form action="{{ route('questions.update', $question) }}" method="POST">

                @csrf
                @method('PUT')

                <div class="modal-header">

                    <h5 class="modal-title" id="editStandaloneQuestionModalLabel{{ $question->id }}">
                        <i class="bi bi-pencil-square me-2"></i>
                        Edit Question
                    </h5>

                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>

                </div>

                <div class="modal-body">

                    <div class="alert alert-info d-flex align-items-start gap-2">
                        <i class="bi bi-info-circle mt-1"></i>
                        <div>
                            This question belongs only to this quiz — changes
                            here won't affect any other quiz.
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Question</label>
                        <textarea name="question" class="form-control" rows="5" required>{{ $question->question }}</textarea>
                    </div>

                    <div class="row">

                        <div class="col-md-6 mb-4">
                            <label class="form-label">Question Type</label>
                            <select class="form-select question-type-select" name="type">
                                <option value="choice" @selected($question->type === 'choice')>Multiple Choice</option>
                                <option value="multiple_select" @selected($question->type === 'multiple_select')>Multiple Select</option>
                                <option value="written" @selected($question->type === 'written')>Written</option>
                                <option value="short_answer" @selected($question->type === 'short_answer')>Short Answer</option>
                                <option value="file" @selected($question->type === 'file')>File Upload</option>
                                <option value="both" @selected($question->type === 'both')>Written + File</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-4">
                            <label class="form-label">Points</label>
                            <input type="number" step="0.01" min="0" name="point" class="form-control"
                                value="{{ $question->point }}">
                        </div>

                    </div>

                    <div class="mb-4">
                        <label class="form-label">Feedback / Explanation</label>
                        <textarea name="explanation" class="form-control" rows="5">{{ $question->explanation }}</textarea>
                    </div>

                    <div>

                        <div class="options-section d-none">

                            <label class="form-label d-flex justify-content-between align-items-center">
                                <span>Answer Options</span>
                                <span class="choice-hint text-muted small d-none">Give the correct option 100%. Options left at 0% earn nothing.</span>
                            </label>

                            <div class="options-container"></div>

                            <button type="button" class="add-option-btn btn btn-secondary-custom btn-sm">
                                <i class="bi bi-plus-circle me-1"></i>
                                Add Option
                            </button>

                        </div>

                        <div class="answers-section d-none">

                            <label class="form-label">Accepted Answers</label>

                            <div class="answers-container"></div>

                            <button type="button" class="add-answer-btn btn btn-secondary-custom btn-sm">
                                <i class="bi bi-plus-circle me-1"></i>
                                Add Accepted Answer
                            </button>

                        </div>

                        <div class="file-section d-none">

                            <label class="form-label">Allowed File Types</label>

                            <div class="file-type-checkboxes mb-3">
                                @foreach(['pdf' => 'PDF', 'doc' => 'DOC', 'docx' => 'DOCX', 'ppt' => 'PPT', 'pptx' => 'PPTX', 'jpg' => 'JPG', 'png' => 'PNG', 'zip' => 'ZIP'] as $ext => $label)
                                <div class="form-check form-check-inline">
                                    <input
                                        type="checkbox"
                                        class="form-check-input file-type-checkbox"
                                        name="file_allowed[]"
                                        value="{{ $ext }}"
                                        id="editStandaloneFileType{{ $question->id }}{{ $ext }}">
                                    <label class="form-check-label" for="editStandaloneFileType{{ $question->id }}{{ $ext }}">{{ $label }}</label>
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
                                        Max File Size
                                        <small class="text-muted">(MB)</small>
                                    </label>
                                    <input type="number" min="1" step="1" class="form-control" name="max_file_size">
                                </div>

                                <div class="col-md-6 mb-4">
                                    <label class="form-label">Max Number of Files</label>
                                    <input type="number" min="1" max="255" step="1" class="form-control" name="max_files">
                                </div>

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