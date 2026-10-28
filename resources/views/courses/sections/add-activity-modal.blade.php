{{-- =========================
     ADD ACTIVITY MODAL
========================= --}}
<div class="modal fade" id="addActivityModal" tabindex="-1">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content custom-modal">

            <div class="modal-header border-0">

                <h4 class="modal-title">
                    Add Activity
                </h4>

                <button type="button"
                    class="btn-close"
                    data-bs-dismiss="modal">
                </button>

            </div>

            <div class="modal-body">

                <p class="text-muted mb-4">
                    What kind of activity do you want to add to this section?
                </p>

                <div class="row g-3">

                    <div class="col-6 d-flex">

                        <a href="{{ route('quizzes.create', $section) }}"
                            class="activity-choice-card d-block text-decoration-none text-dark w-100">

                            <div class="activity-choice-icon">
                                <i class="bi bi-patch-question-fill"></i>
                            </div>

                            <h6 class="mb-1">Quiz</h6>

                            <small class="text-muted">
                                Auto-graded or manually reviewed questions
                            </small>

                        </a>

                    </div>

                    <div class="col-6 d-flex">

                        <a href="{{ route('assignments.create', $section) }}"
                            class="activity-choice-card d-block text-decoration-none text-dark w-100">

                            <div class="activity-choice-icon">
                                <i class="bi bi-file-earmark-text-fill"></i>
                            </div>

                            <h6 class="mb-1">Assignment</h6>

                            <small class="text-muted">
                                Students submit work for manual grading
                            </small>

                        </a>

                    </div>

                </div>

            </div>

            <div class="modal-footer border-0">

            </div>

        </div>

    </div>

</div>