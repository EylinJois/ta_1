{{-- Create Question Modal --}}
<div class="modal fade question-modal"
    id="createQuestionModal"
    tabindex="-1"
    aria-labelledby="createQuestionModalLabel"
    aria-hidden="true">

    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-fullscreen-sm-down">

        <div class="modal-content custom-modal">

            <form action="{{ route('questions.store') }}"
                method="POST">

                @csrf

                <input type="hidden"
                    name="question_bank_id"
                    value="{{ $questionBank->id }}">

                {{-- Header --}}
                <div class="modal-header">

                    <h5 class="modal-title"
                        id="createQuestionModalLabel">

                        <i class="bi bi-plus-circle me-2"></i>

                        Add Question

                    </h5>

                    <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal">
                    </button>

                </div>

                {{-- Body --}}
                <div class="modal-body">

                    {{-- Question --}}
                    <div class="mb-4">

                        <label class="form-label">
                            Question
                        </label>

                        <textarea
                            name="question"
                            class="form-control"
                            rows="5"
                            placeholder="Type your question..."
                            required></textarea>

                    </div>

                    <div class="row">

                        {{-- Type --}}
                        <div class="col-md-6 mb-4">

                            <label class="form-label">
                                Question Type
                            </label>

                            <select
                                class="form-select question-type-select"
                                name="type">

                                <option value="choice">
                                    Multiple Choice
                                </option>

                                <option value="multiple_select">
                                    Multiple Select
                                </option>

                                <option value="written">
                                    Written
                                </option>

                                <option value="short_answer">
                                    Short Answer
                                </option>

                                <option value="file">
                                    File Upload
                                </option>

                                <option value="both">
                                    Written + File
                                </option>

                            </select>

                        </div>

                        {{-- Point --}}
                        <div class="col-md-6 mb-4">

                            <label class="form-label">
                                Points
                            </label>

                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                name="point"
                                class="form-control"
                                placeholder="10">

                        </div>

                    </div>

                    {{-- Explanation --}}
                    <div class="mb-4">

                        <label class="form-label">

                            Feedback / Explanation

                        </label>

                        <textarea
                            name="explanation"
                            class="form-control"
                            rows="5"
                            placeholder="Displayed after the student submits the question..."></textarea>

                    </div>

                    {{-- Dynamic Area --}}
                    <div>

                        {{-- MULTIPLE CHOICE / MULTIPLE SELECT --}}
                        <div class="options-section d-none">

                            <label class="form-label d-flex justify-content-between align-items-center">
                                <span>Answer Options</span>
                                <span class="choice-hint text-muted small d-none">
                                    Check the one correct option
                                </span>
                            </label>

                            <div class="options-container"></div>

                            <button type="button"
                                class="add-option-btn btn btn-secondary-custom btn-sm">
                                <i class="bi bi-plus-circle me-1"></i>
                                Add Option
                            </button>

                        </div>

                        {{-- SHORT ANSWER --}}
                        <div class="answers-section d-none">

                            <label class="form-label">
                                Accepted Answers
                            </label>

                            <div class="answers-container"></div>

                            <button type="button"
                                class="add-answer-btn btn btn-secondary-custom btn-sm">
                                <i class="bi bi-plus-circle me-1"></i>
                                Add Accepted Answer
                            </button>

                        </div>

                        {{-- FILE UPLOAD --}}
                        <div class="file-section d-none">

                            <label class="form-label">
                                Allowed File Types
                            </label>

                            <div class="file-type-checkboxes mb-3">
                                @foreach(['pdf' => 'PDF', 'doc' => 'DOC', 'docx' => 'DOCX', 'ppt' => 'PPT', 'pptx' => 'PPTX', 'jpg' => 'JPG', 'png' => 'PNG', 'zip' => 'ZIP'] as $ext => $label)
                                <div class="form-check form-check-inline">
                                    <input
                                        type="checkbox"
                                        class="form-check-input file-type-checkbox"
                                        name="file_allowed[]"
                                        value="{{ $ext }}"
                                        id="createFileType{{ $ext }}">
                                    <label class="form-check-label" for="createFileType{{ $ext }}">{{ $label }}</label>
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
                                        <small class="text-muted">(MB) - Default 2 MB</small>
                                    </label>
                                    <input
                                        type="number"
                                        min="1"
                                        step="1"
                                        class="form-control"
                                        name="max_file_size"
                                        placeholder="e.g. 2">
                                </div>

                                <div class="col-md-6 mb-4">
                                    <label class="form-label">
                                        Max Number of Files - Default 1 File
                                    </label>
                                    <input
                                        type="number"
                                        min="1"
                                        max="255"
                                        step="1"
                                        class="form-control"
                                        name="max_files"
                                        placeholder="e.g. 1">
                                </div>

                            </div>

                        </div>

                    </div>

                </div>

                {{-- Footer --}}
                <div class="modal-footer">

                    <button type="button"
                        class="btn btn-secondary-custom"
                        data-bs-dismiss="modal">

                        Cancel

                    </button>

                    <button type="submit"
                        class="btn btn-theme">

                        <i class="bi bi-check-circle me-1"></i>

                        Save Question

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>