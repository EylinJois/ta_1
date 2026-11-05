<div class="modal fade"
     id="editQuestionBankModal"
     tabindex="-1">

    <div class="modal-dialog modal-lg modal-dialog-scrollable">

        <div class="modal-content custom-modal">

            <form action="{{ route('questionbank.update', $questionBank) }}"
                  method="POST">

                @csrf
                @method('PUT')

                <div class="modal-header">

                    <h5 class="modal-title">

                        <i class="bi bi-pencil-square me-2"></i>

                        Edit Question Bank

                    </h5>

                    <button class="btn-close"
                            data-bs-dismiss="modal"></button>

                </div>

                <div class="modal-body">

                    {{-- Name --}}
                    <div class="mb-4">

                        <label class="form-label">

                            Name

                        </label>

                        <input
                            type="text"
                            class="form-control"
                            name="name"
                            value="{{ old('name', $questionBank->name) }}"
                            required>

                    </div>

                    {{-- Description --}}
                    <div class="mb-4">

                        <label class="form-label">

                            Description

                        </label>

                        <textarea
                            class="form-control"
                            rows="5"
                            name="description">{{ old('description', $questionBank->description) }}</textarea>

                    </div>

                    {{-- Intended Course --}}
                    <div class="mb-4">

                        <label class="form-label">

                            Intended Course

                        </label>

                        <select
                            class="form-select"
                            name="course_id">

                            <option value="">

                                None

                            </option>

                            @foreach($courses as $course)

                                <option value="{{ $course->id }}"
                                    @selected($questionBank->course_id == $course->id)>

                                    {{ $course->name }}

                                </option>

                            @endforeach

                        </select>

                    </div>

                    {{-- Visibility --}}
                    <div class="mb-3">

                        <label class="form-label">

                            Visibility

                        </label>

                        <select
                            class="form-select"
                            name="visibility">

                            <option value="1"
                                @selected($questionBank->visibility)>

                                Public

                            </option>

                            <option value="0"
                                @selected(!$questionBank->visibility)>

                                Private

                            </option>

                        </select>

                    </div>

                </div>

                <div class="modal-footer">

                    <button class="btn btn-secondary-custom"
                            data-bs-dismiss="modal">

                        Cancel

                    </button>

                    <button class="btn btn-theme">

                        <i class="bi bi-check-circle"></i>

                        Save Changes

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>