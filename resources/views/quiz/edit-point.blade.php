{{-- Edit Point Override (works for both bank and quiz-only questions) --}}
<div class="modal fade"
    id="editPointModal{{ $question->id }}"
    tabindex="-1"
    aria-labelledby="editPointModalLabel{{ $question->id }}"
    aria-hidden="true">

    <div class="modal-dialog modal-md modal-dialog-scrollable">

        <div class="modal-content custom-modal">

            <form action="{{ route('quiz-questions.point-override', [$quiz, $question]) }}" method="POST">

                @csrf
                @method('PATCH')

                <div class="modal-header">

                    <h5 class="modal-title" id="editPointModalLabel{{ $question->id }}">
                        <i class="bi bi-pencil-square me-2"></i>
                        Edit Point
                    </h5>

                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>

                </div>

                <div class="modal-body">

                    <div class="mb-2">
                        <label class="form-label">Points</label>
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            name="point_override"
                            class="form-control"
                            placeholder="{{ $question->point }} pt"
                            value="{{ $question->pivot->point_override }}">
                        <small class="text-muted">
                            Leave blank to use this question's default of {{ rtrim(rtrim(number_format($question->point, 2), '0'), '.') }} pts for this quiz.
                        </small>
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
