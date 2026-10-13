@extends('layouts.app')

@section('title', "Progress Report")

@push('styles')
<link rel="stylesheet" href="{{ asset('css/course.css') }}">
@endpush

@section('content')

<div class="mb-4">
    <a href="{{ back()->getTargetUrl() }}" class="btn btn-icon-plain">
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
            @if($fromCourse)
            <div class="text-muted small">{{ $student->email }} · {{ $course->name}}</div>
            @else
            <div class="text-muted small">{{ $reports->first()->origin_name }}</div>
            @endif
        </div>

        @if($isManager)
        <button type="button" class="btn btn-theme" data-bs-toggle="modal" data-bs-target="#addProgressModal">
            <i class="bi bi-plus-circle"></i>
            Add Report
        </button>
        @endif

    </div>
</div>

{{-- Progress Report --}}
<div class="table-card">


    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="section-title mb-0">Progress Report</h4>
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
<th style="width: 15%; white-space: nowrap;">Period</th>
<th>Progress</th>
<th>Avg Scores</th>
<th>Comment</th>
<th>Last Updated</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reports as $report)
                <tr>
                    <td class="fw-semibold">{{ $report->title }}</td>
                    <td class="text-nowrap">
    @if($report->start_period || $report->end_period)
        <div>{{ $report->start_period?->format('d M y') ?? '—' }} - {{ $report->end_period?->format('d M y') ?? '—' }}</div>
    @else
        —
    @endif
</td>
                    <td>
                        <div class="d-flex align-items-center gap-2 wrap" style="max-width: 200px;">
                            <div class="progress flex-grow-1" style="height: 8px; width: 100px; border-radius: 999px;">
                                <div class="progress-bar bg-danger" style="width: {{ $report->progress }}%; border-radius: 999px;"></div>
                            </div>
                            <span class="small text-muted" style="line-height: 1;">{{ $report->progress }}%</span>
                        </div>
                    </td>
                    <td>
                        {{ !is_null($report->avg_all_score) ? rtrim(rtrim(number_format($report->avg_all_score, 2), '0'), '.') : '—' }}%
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

{{-- Score History --}}
<div class="row g-4 mt-0">

    {{-- Quiz scores --}}
    <div class="col-12 col-lg-6">
        <div class="table-card h-100">

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
                <h4 class="section-title mb-0">Quiz Scores</h4>
                <span class="badge bg-danger">
                    {{ $attempts->count() }} {{ Str::plural('Attempt', $attempts->count()) }}
                </span>
            </div>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Quiz</th>
                            <th>Score</th>
                            <th>Status</th>
                            <th class="text-nowrap">Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attempts as $attempt)
                        <tr>
                            <td class="fw-semibold">
                                {{ $attempt->quiz?->title ?? $attempt->quiz?->name ?? 'Deleted quiz' }}
                            </td>
                            <td>
                                {{ !is_null($attempt->score) ? rtrim(rtrim(number_format($attempt->score, 2), '0'), '.') : '—' }}%
                            </td>
                            <td>
                                <span class="badge {{ $attempt->status === 'graded' ? 'bg-success' : 'bg-secondary' }}">
                                    {{ ucfirst($attempt->status) }}
                                </span>
                            </td>
                            <td class="text-nowrap">{{ $attempt->created_at?->format('d M Y, H:i') ?? '—' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">No quiz attempts yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    {{-- Assignment scores --}}
    <div class="col-12 col-lg-6">
        <div class="table-card h-100">

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
                <h4 class="section-title mb-0">Assignment Scores</h4>
                <span class="badge bg-danger">
                    {{ $submissions->count() }} {{ Str::plural('Submission', $submissions->count()) }}
                </span>
            </div>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Assignment</th>
                            <th>Score</th>
                            <th class="text-nowrap">Submitted At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($submissions as $submission)
                        <tr>
                            <td class="fw-semibold">
                                {{ $submission->assignment?->title ?? $submission->assignment?->name ?? 'Deleted assignment' }}
                            </td>
                            <td>
                                {{ !is_null($submission->score) ? rtrim(rtrim(number_format($submission->score, 2), '0'), '.') . '%' : 'Not graded' }}
                            </td>
                            <td class="text-nowrap">{{ $submission->submitted_at?->format('d M Y, H:i') ?? '—' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center py-4 text-muted">No assignment submissions yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
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
                            <label class="form-label fw-semibold">Avg. All Scores</label>
                            <input type="number" step="0.01" name="avg_all_score" class="form-control quiz-score-input" min="0" placeholder="Auto-calculated if left blank">
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

        const courseId = {{ $course->id ?? 0}};

        document.querySelectorAll('.calculate-btn').forEach(function(btn) {

            const defaultLabel = '<i class="bi bi-calculator"></i> Calculate';

            btn.addEventListener('click', function() {

                const studentId = btn.dataset.studentId;
                const modalBody = btn.closest('.modal-body');
                const progressInput = modalBody.querySelector('.progress-input');
                const scoreInput = modalBody.querySelector('.quiz-score-input');

                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Calculating...';

                fetch(`/courses/${courseId}/progress/calculate/${studentId}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                    .then(function(r) {
                        if (!r.ok) throw new Error('Request failed: ' + r.status);
                        return r.json();
                    })
                    .then(function(data) {
                        progressInput.value = data.progress;
                        scoreInput.value = data.avg_all_score ?? data.avg_quiz_score ?? '';
                    })
                    .catch(function() {
                        alert('Failed to calculate progress.');
                    })
                    .finally(function() {
                        btn.disabled = false;
                        btn.innerHTML = defaultLabel;
                    });
            });

        });

        // Avoids the aria-hidden warning when the modal closes with focus inside it
        document.addEventListener('hide.bs.modal', function() {
            if (document.activeElement) document.activeElement.blur();
        });

    });
</script>
@endpush

@endsection