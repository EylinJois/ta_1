@extends('layouts.app')

@section('title', $student->name . "'s Progress - " . $course->name)

@section('page-title', 'Student Progress')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/course.css') }}">
@endpush

@section('content')

<div class="mb-4">
    <a href="{{ route('courses.show', $course) }}" class="btn btn-icon-plain">
        <i class="bi bi-arrow-left"></i>
    </a>
</div>

{{-- Student header --}}
<div class="table-card mb-4">
    <div class="student-progress-header">
        @if(!empty($student->img))
        <img src="{{ asset('storage/' . $student->img) }}"
            width="52" height="52" class="rounded-circle student-progress-avatar">
        @else
        <img src="https://ui-avatars.com/api/?name={{ urlencode($student->name ?? 'User') }}"
            width="52" height="52" class="rounded-circle student-progress-avatar">
        @endif

        <div class="student-progress-info">
            <h4 class="section-title mb-0">{{ $student->name }}'s Progress</h4>
            <div class="text-muted small">{{ $student->email }} · {{ $course->name }}</div>
        </div>

        @if($isManager)
        <button type="button" class="btn btn-theme" data-bs-toggle="modal" data-bs-target="#addProgressModal">
            <i class="bi bi-plus-circle"></i>
            Add Report
        </button>
        @endif

    </div>
</div>

{{-- Progress history --}}
<div class="table-card">


    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="section-title mb-0">Progress History</h4>
        <span class="badge bg-danger">
            {{ $reports->count() }}
            {{ Str::plural('Report', $reports->count()) }}
        </span>
    </div>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Period</th>
                    <th>Progress</th>
                    <th>Quiz Avg</th>
                    <th>Comment</th>
                    <th>Last Updated</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reports as $report)
                <tr>
                    <td class="fw-semibold">{{ $report->title }}</td>
                    <td>
                        @if($report->start_period || $report->end_period)
                        {{ $report->start_period?->format('d M Y') }} – {{ $report->end_period?->format('d M Y') }}
                        @else
                        —
                        @endif
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="progress flex-grow-1" style="height: 8px; width: 100px; border-radius: 999px;">
                                <div class="progress-bar bg-danger" style="width: {{ $report->progress }}%; border-radius: 999px;"></div>
                            </div>
                            <span class="small text-muted" style="line-height: 1;">{{ $report->progress }}%</span>
                        </div>
                    </td>
                    <td>
                        {{ !is_null($report->avg_quiz_score) ? rtrim(rtrim(number_format($report->avg_quiz_score, 2), '0'), '.') : '—' }}
                    </td>
                    <td>{{ $report->comment ?: '—' }}</td>
                    <td>{{ $report->updated_at->format('d M Y') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-5">
                        No progress reports yet.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

    </div>

</div>

{{-- Add Progress Report Modal --}}
@if($isManager)
<div class="modal fade" id="addProgressModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-modal">
            <form action="{{ route('progress.store', $course) }}" method="POST">
                @csrf
                <input type="hidden" name="user_id" value="{{ $student->id }}">

                <div class="modal-header border-0">
                    <div>
                        <h5 class="modal-title fw-bold">Add Progress Report</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Title</label>
                        <input type="text" name="title" class="form-control" required placeholder="e.g. Midterm Progress">
                    </div>

                    <div class="row">
                        <div class="col-6 mb-4">
                            <label class="form-label fw-semibold">Start Period</label>
                            <input type="date" name="start_period" class="form-control">
                        </div>
                        <div class="col-6 mb-4">
                            <label class="form-label fw-semibold">End Period</label>
                            <input type="date" name="end_period" class="form-control">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-2">
                            <label class="form-label fw-semibold">Progress (%)</label>
                            <input type="number" name="progress" class="form-control progress-input" min="0" max="100" placeholder="Auto-calculated if left blank">
                        </div>
                        <div class="col-6 mb-2">
                            <label class="form-label fw-semibold">Avg. Quiz Score</label>
                            <input type="number" step="0.01" name="avg_quiz_score" class="form-control quiz-score-input" min="0" placeholder="Auto-calculated if left blank">
                        </div>
                    </div>

                    <button type="button" class="btn btn-secondary-custom btn-sm mb-4 calculate-btn" data-student-id="{{ $student->id }}">
                        <i class="bi bi-calculator"></i>
                        Calculate
                    </button>

                    <div class="mb-2">
                        <label class="form-label fw-semibold">Comment</label>
                        <textarea name="comment" class="form-control" rows="3"></textarea>
                    </div>

                </div>

                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-theme">
                        <i class="bi bi-check-circle-fill"></i>
                        Save Report
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {

        const courseId = {
            {
                $course -> id
            }
        };

        document.querySelectorAll('.calculate-btn').forEach(function(btn) {

            btn.addEventListener('click', function() {

                const studentId = btn.dataset.studentId;
                const modalBody = btn.closest('.modal-body');
                const progressInput = modalBody.querySelector('.progress-input');
                const scoreInput = modalBody.querySelector('.quiz-score-input');

                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Calculating...';

                fetch(`/courses/${courseId}/progress/calculate/${studentId}`)
                    .then(r => r.json())
                    .then(function(data) {
                        progressInput.value = data.progress;
                        scoreInput.value = data.avg_quiz_score ?? '';
                    })
                    .catch(function() {
                        alert('Failed to calculate progress.');
                    })
                    .finally(function() {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="bi bi-calculator"></i> Calculate From Course Data';
                    });
            });

        });

    });
</script>
@endpush

@endsection