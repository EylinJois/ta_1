@extends('layouts.app')

@section('title', 'My Calendar')

@section('page-title', 'Calendar')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/calendar.css') }}">
<link rel="stylesheet" href="{{ asset('css/course.css') }}">
<link rel="stylesheet" href="{{ asset('css/qbank.css') }}">
@endpush

@section('content')

<div class="courses-header mb-4">

    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('calendar.index', ['date' => $startOfWeek->copy()->subWeek()->format('Y-m-d')]) }}"
            class="btn">
            <i class="bi bi-chevron-left"></i>
        </a>
        <h3 class="mb-0">
            {{ $startOfWeek->format('F Y') }}
        </h3>
        <a href="{{ route('calendar.index', ['date' => $startOfWeek->copy()->addWeek()->format('Y-m-d')]) }}"
            class="btn">
            <i class="bi bi-chevron-right"></i>
        </a>
    </div>

    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('calendar.index') }}" class="btn btn-secondary-cal" title="Go to current week">
            <svg xmlns="http://www.w3.org/2000/svg" width="1.5em" height="1.5em" viewBox="0 0 24 24">
                <path d="M0 0h24v24H0z" fill="none" />
                <path fill="currentColor" d="M17 3.34a10 10 0 1 1-14.995 8.984L2 12l.005-.324A10 10 0 0 1 17 3.34M12 6a1 1 0 0 0-.993.883L11 7v5l.009.131a1 1 0 0 0 .197.477l.087.1l3 3l.094.082a1 1 0 0 0 1.226 0l.094-.083l.083-.094a1 1 0 0 0 0-1.226l-.083-.094L13 11.585V7l-.007-.117A1 1 0 0 0 12 6" />
            </svg>
        </a>
        <button type="button" class="btn btn-theme" data-bs-toggle="modal" data-bs-target="#createEventModal">
            <i class="bi bi-plus-circle"></i>
            Add Event
        </button>
    </div>

</div>

<div class="week-grid">

    @foreach($days as $day)
    @php
    $dayActivities = $activities->get($day->format('Y-m-d'), collect());
    $isToday = $day->isToday();
    @endphp

    <div class="week-day-column">

        <div class="week-day-header {{ $isToday ? 'is-today' : '' }}">
            <span class="week-day-name">{{ $day->format('D') }}</span>
            <div class="week-day-num">{{ $day->day }}</div>
            <button type="button" class="week-add-btn" onclick="openCreateModal('{{ $day->format('Y-m-d') }}')">
                <i class="bi bi-plus-circle"></i>
            </button>
        </div>

        <div class="week-event-list">

            @forelse($dayActivities as $activity)
            @php
            $typeClass = match($activity->activityType->name) {
            'Study Session' => 'type-study',
            'Quiz' => 'type-quiz',
            'Assignment' => 'type-assignment',
            'Meeting' => 'type-meeting',
            'Reminder' => 'type-personal',
            };
            $statusClass = match($activity->status) {
            'completed' => 'status-completed',
            'cancelled' => 'status-cancelled',
            'missed' => 'status-missed',
            default => '',
            };
            @endphp
            <div class="week-event-item {{ $typeClass }} {{ $statusClass }}" onclick="openEventDetail({{ $activity->id }})">
                <div class="week-event-time">
                    @if($activity->is_all_day)
                    All day
                    @else
                    {{ $activity->starts_at->format('H:i') }}
                    @endif
                </div>
                <div class="week-event-name">{{ $activity->name }}</div>
                <div class="week-event-type">{{ $activity->activityType->name }}</div>
            </div>
            @empty
            <div class="week-empty-day">No events</div>
            @endforelse

        </div>

    </div>
    @endforeach

</div>

{{-- CREATE EVENT MODAL — one single shared modal, not per-day --}}
<div class="modal fade" id="createEventModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-modal">
            <form action="{{ route('calendar.events.store') }}" method="POST">
                @csrf

                @if($errors->any())
                <div class="alert alert-danger m-3 mb-0">
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <div class="modal-header border-0">
                    <h5 class="modal-title">New Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">

                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" name="event_date" id="createEventDate" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Starts</label>
                            <input type="time" name="starts_time" id="createStartsTime" class="form-control" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Ends <small class="text-muted">(optional)</small></label>
                            <input type="time" name="ends_time" id="createEndsTime" class="form-control">
                        </div>
                    </div>

                    <div class="form-check mb-3">
                        <input type="hidden" name="is_all_day" value="0">
                        <input type="checkbox" class="form-check-input" id="isAllDayCheck" name="is_all_day" value="1">
                        <label class="form-check-label" for="isAllDayCheck">All-day event</label>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Type</label>
                            <select name="type_id" class="form-select" required>
                                @foreach($activityTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Course <small class="text-muted">(optional)</small></label>
                            <select name="course_id" class="form-select">
                                <option value="">None</option>
                                @foreach($courses as $course)
                                <option value="{{ $course->id }}">{{ $course->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Meeting Link <small class="text-muted">(optional)</small></label>
                        <input type="url" name="meeting_link" class="form-control" placeholder="https://...">
                    </div>

                    <div class="mb-1">
                        <label class="form-label">Remind me before</label>
                        <select name="notify_minutes" class="form-select">
                            <option value="0">At the time of the event</option>
                            <option value="15" selected>15 minutes before</option>
                            <option value="30">30 minutes before</option>
                            <option value="60">1 hour before</option>
                            <option value="1440">1 day before</option>
                        </select>
                    </div>

                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary-custom" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-theme">
                        <i class="bi bi-check-circle"></i>
                        Add Event
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- EVENT DETAIL MODAL --}}
<div class="modal fade" id="eventDetailModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-modal">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="eventDetailTitle">Event</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="eventDetailBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-danger"></div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

       
        window.openCreateModal = function (dateStr) {
            document.getElementById('createEventDate').value = dateStr;
            document.getElementById('createStartsTime').value = '08:00';
            document.getElementById('createEndsTime').value = '';
            new bootstrap.Modal(document.getElementById('createEventModal')).show();
        };

        document.getElementById('createEventModal').addEventListener('shown.bs.modal', function () {
            const dateField = document.getElementById('createEventDate');
            const startsTimeField = document.getElementById('createStartsTime');

            if (!dateField.value) {
                dateField.value = new Date().toISOString().split('T')[0];
            }
            if (!startsTimeField.value) {
                startsTimeField.value = '08:00';
            }
        });

        window.openEventDetail = function(activityId) {

            const modalEl = document.getElementById('eventDetailModal');
            const body = document.getElementById('eventDetailBody');
            const title = document.getElementById('eventDetailTitle');

            body.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-danger"></div></div>';
            title.textContent = 'Event';

            new bootstrap.Modal(modalEl).show();

            fetch(`/calendar/events/${activityId}`, {
                    headers: { 'Accept': 'application/json' },
                })
                .then(r => r.json())
                .then(function(activity) {

                    title.textContent = activity.name;

                    const isSystemGenerated = !!activity.source_type_id;
                    const start = new Date(activity.starts_at).toLocaleString();
                    const end = activity.ends_at ? new Date(activity.ends_at).toLocaleString() : null;

                    const statusBadgeClass = {
                        scheduled: 'bg-light text-dark',
                        completed: 'bg-success',
                        cancelled: 'bg-secondary',
                        missed: 'bg-danger',
                    }[activity.status] || 'bg-light text-dark';

                    const canChangeStatus = activity.status === 'scheduled';

                    body.innerHTML = `
                        ${activity.description ? `<p>${activity.description}</p>` : ''}
                        <p class="mb-1"><strong>Starts:</strong> ${start}</p>
                        ${end ? `<p class="mb-1"><strong>Ends:</strong> ${end}</p>` : ''}
                        ${activity.course ? `<p class="mb-1"><strong>Course:</strong> ${activity.course.name}</p>` : ''}
                        ${activity.meeting_link ? `<p class="mb-1"><a href="${activity.meeting_link}" target="_blank">Join meeting</a></p>` : ''}
                        <span class="badge ${statusBadgeClass}">${activity.status}</span>
                        ${isSystemGenerated ? '<p class="text-muted small mt-3">This event is automatically managed and its schedule cannot be edited directly, but you can still mark it complete or cancelled.</p>' : ''}
                        ${canChangeStatus ? `
                            <div class="d-flex gap-2 mt-3">
                                <button type="button" class="btn btn-success btn-sm" id="completeEventBtn">
                                    <i class="bi bi-check-circle"></i> Mark Complete
                                </button>
                                <button type="button" class="btn btn-secondary-custom btn-sm" id="cancelEventBtn">
                                    <i class="bi bi-x-circle"></i> Cancel Event
                                </button>
                            </div>
                        ` : ''}
                    `;

                    const completeBtn = document.getElementById('completeEventBtn');
                    if (completeBtn) {
                        completeBtn.addEventListener('click', function() {
                            updateEventStatus(activity.id, 'completed');
                        });
                    }

                    const cancelBtn = document.getElementById('cancelEventBtn');
                    if (cancelBtn) {
                        cancelBtn.addEventListener('click', function() {
                            if (confirm('Cancel this event?')) {
                                updateEventStatus(activity.id, 'cancelled');
                            }
                        });
                    }

                    function updateEventStatus(id, status) {
                        fetch(`/calendar/events/${id}/status`, {
                                method: 'PATCH',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken,
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify({ status }),
                            })
                            .then(function(r) {
                                if (!r.ok) throw new Error();
                                window.location.reload();
                            })
                            .catch(function() {
                                alert('Failed to update event status. Please try again.');
                            });
                    }
                })
                .catch(function() {
                    body.innerHTML = '<p class="text-danger">Failed to load event details.</p>';
                });
        };

        @if($errors->any())
        new bootstrap.Modal(document.getElementById('createEventModal')).show();
        @endif

    });
</script>
@endpush

@endsection