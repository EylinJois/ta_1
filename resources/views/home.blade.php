@extends('layouts.app')

@section('title', 'Home')

@section('page-title', 'Home')

@section('content')

@php
    $roleBadges = [
        'admin'   => 'bg-danger',
        'teacher' => 'bg-warning text-dark',
        'student' => 'bg-success',
    ];

    $roleLine = $roleSummary
        ->map(fn ($count, $role) => ucfirst($role) . ' in ' . $count . ' ' . Str::plural('class', $count))
        ->implode(' · ');
@endphp

{{-- Welcome Section --}}
<div class="welcome-banner mb-4">
    <div>
        <h2>Welcome Back, {{ auth()->user()->name }}</h2>
        <p class="mb-0">
            @if($roleLine)
                {{ $roleLine }}
            @else
                You are not in any class yet.
            @endif
        </p>
    </div>
</div>

@if($memberships->isEmpty())

<div class="table-card text-center py-5">
    <i class="bi bi-journal-x fs-1 text-muted"></i>
    <h5 class="mt-3">No classes yet</h5>
    <p class="text-muted mb-0">
        Ask a course admin to add you to a class, or create your own course.
    </p>
</div>

@else

{{-- My Classes --}}
<h5 class="section-title mb-3">My Classes</h5>
<div class="row g-4 mb-4">
    @foreach($memberships as $class)
    <div class="col-lg-4 col-md-6">
        <a href="{{ route('courses.show', $class->course_id) }}" class="text-decoration-none text-dark">
            <div class="home-card">
                <div>
                    <p class="card-label mb-1">{{ $class->course->name }}</p>
                    <h5 class="mb-2">{{ $class->name }}</h5>
                    <span class="badge {{ $roleBadges[strtolower($class->pivot->role)] ?? 'bg-secondary' }}">
                        {{ ucfirst(strtolower($class->pivot->role)) }}
                    </span>
                </div>
                <div class="card-icon">
                    <i class="bi bi-journal-richtext"></i>
                </div>
            </div>
        </a>
    </div>
    @endforeach
</div>

{{-- Stat cards --}}
@php
    $statGroups = [];

    if ($learner) {
        $statGroups[] = [
            'title' => 'Learning',
            'stats' => [
                ['label' => 'Classes Enrolled', 'value' => $studentClasses->count(), 'icon' => 'bi-journal-richtext'],
                ['label' => 'Due This Week', 'value' => $learner['dueSoon'], 'icon' => 'bi-alarm-fill'],
                [
                    'label' => 'Average Quiz Score',
                    'value' => is_null($learner['avgScore'])
                        ? '—'
                        : rtrim(rtrim(number_format($learner['avgScore'], 2), '0'), '.'),
                    'icon'  => 'bi-trophy-fill',
                ],
                [
                    'label' => 'Latest Progress',
                    'value' => is_null($learner['latestProgress']) ? '—' : $learner['latestProgress'] . '%',
                    'icon'  => 'bi-graph-up-arrow',
                ],
            ],
        ];
    }

    if ($manager) {
        $statGroups[] = [
            'title' => 'Teaching & Managing',
            'stats' => [
                ['label' => 'Classes Managed', 'value' => $manager['classCount'], 'icon' => 'bi-diagram-3-fill'],
                ['label' => 'Students', 'value' => $manager['studentCount'], 'icon' => 'bi-people-fill'],
                ['label' => 'Open Quizzes', 'value' => $manager['openQuizzes'], 'icon' => 'bi-pencil-square'],
                [
                    'label'     => 'Awaiting Grading',
                    'value'     => $manager['pendingGrading'],
                    'icon'      => 'bi-clipboard-check-fill',
                    'note'      => $manager['pendingGrading'] > 0 ? 'Needs review' : null,
                    'noteClass' => 'growth-down',
                ],
            ],
        ];
    }
@endphp

@foreach($statGroups as $group)
<h6 class="text-uppercase text-muted small mb-2">{{ $group['title'] }}</h6>

<div class="row g-4 mb-4">
    @foreach($group['stats'] as $stat)
    <div class="col-lg-3 col-md-6">
        <div class="home-card">

            <div>
                <p class="card-label">{{ $stat['label'] }}</p>

                <h2>{{ $stat['value'] }}</h2>

                @if(!empty($stat['note']))
                <span class="{{ $stat['noteClass'] ?? 'growth-up' }}">
                    {{ $stat['note'] }}
                </span>
                @endif
            </div>

            <div class="card-icon">
                <i class="bi {{ $stat['icon'] }}"></i>
            </div>

        </div>
    </div>
    @endforeach
</div>
@endforeach

{{-- Main Content --}}
<div class="row">

    {{-- Left column --}}
    <div class="col-lg-8">

        @if($learner)

        {{-- Recent results --}}
        <div class="table-card mb-4">
            <h4 class="section-title mb-4">Recent Results</h4>

            @if($learner['recentResults']->isNotEmpty())
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Activity</th>
                            <th>Name</th>
                            <th>Score</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($learner['recentResults'] as $result)
                        <tr>
                            <td>{{ $result->quiz->name }}</td>
                            <td>{{ $result->score ?? '—' }}</td>
                            <td>
                                <span class="badge {{ $result->status === 'graded' ? 'bg-success' : 'bg-warning text-dark' }}">
                                    {{ ucfirst($result->status) }}
                                </span>
                            </td>
                            <td>{{ $result->updated_at->format('d M Y') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <p class="text-muted mb-0">No quiz results yet.</p>
            @endif
        </div>

        @endif

        @if($manager)

        {{-- Latest student activity --}}
        <div class="table-card mb-4">
            <h4 class="section-title mb-4">Latest Student Activity</h4>

            @if($manager['activity']->isNotEmpty())
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Class</th>
                            <th>Quiz</th>
                            <th>Score</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($manager['activity'] as $row)
                        @php $attempt = $row['attempt']; @endphp
                        <tr>
                            <td>
                                <div class="student-info">
                                    <img src="https://ui-avatars.com/api/?name={{ urlencode($attempt->user->name ?? 'User') }}">
                                    <div><strong>{{ $attempt->user->name ?? '-' }}</strong></div>
                                </div>
                            </td>
                            <td>{{ $row['classes'] }}</td>
                            <td>{{ $attempt->quiz->title }}</td>
                            <td>{{ $attempt->score ?? '—' }}</td>
                            <td>
                                <span class="badge {{ $attempt->status === 'graded' ? 'bg-success' : 'bg-warning text-dark' }}">
                                    {{ $attempt->status === 'graded' ? 'Graded' : 'Needs grading' }}
                                </span>
                            </td>
                            <td>{{ $attempt->updated_at->format('d M Y') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <p class="text-muted mb-0">No student activity yet.</p>
            @endif
        </div>

        @endif

    </div>

    {{-- Right sidebar --}}
    <div class="col-lg-4">

        {{-- Upcoming schedule --}}
        <div class="table-card mb-4">
            <h4 class="section-title">Upcoming Schedule</h4>

            @forelse($schedule as $event)
            <div class="schedule-item">
                <div class="schedule-icon">
                    <i class="bi {{ \App\Models\Activity::TYPE_ICONS[$event->type] ?? \App\Models\Activity::DEFAULT_ICON }}"></i>
                </div>
                <div>
                    <p class="mb-0">{{ $event['name'] }} - <strong>{{ $event['type'] }}</strong></p>
                    <p class="mb-0 text-muted">
                        {{ $event['label'] }} {{ $event->starts_at->format('M j • g:i A') }}
                        · {{ $event->source_id ? $event->source->name : '' }}
                    </p>
                </div>
            </div>
            @empty
            <p class="text-muted mb-0">Nothing scheduled in the next 14 days.</p>
            @endforelse
        </div>

        {{-- Needs attention (managers) --}}
        @if($manager)
        <div class="table-card mb-4">
            <h4 class="section-title">Needs Attention</h4>

            @if($manager['pendingGrading'] > 0)
            <div class="schedule-item">
                <div class="schedule-icon"><i class="bi bi-clipboard-check-fill"></i></div>
                <div>
                    <strong>{{ $manager['pendingGrading'] }} {{ Str::plural('attempt', $manager['pendingGrading']) }} to grade</strong>
                    <p class="mb-0 text-muted">Submitted by students in your classes</p>
                </div>
            </div>
            @endif

            @foreach($manager['closingSoon'] as $quiz)
            <div class="schedule-item">
                <div class="schedule-icon"><i class="bi bi-hourglass-split"></i></div>
                <div>
                    <strong>{{ $quiz->title }}</strong>
                    <p class="mb-0 text-muted">
                        Closes {{ $quiz->due_at->format('M j • g:i A') }} · {{ $quiz->section->course->name }}
                    </p>
                </div>
            </div>
            @endforeach

            @if($manager['pendingGrading'] === 0 && $manager['closingSoon']->isEmpty())
            <p class="text-muted mb-0">Nothing needs attention right now.</p>
            @endif
        </div>
        @endif
    </div>

</div>

@endif

@endsection