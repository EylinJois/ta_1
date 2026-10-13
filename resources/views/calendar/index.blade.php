@extends('layouts.app')

@section('title', 'My Calendar')

@section('page-title', 'Calendar')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/calendar.css') }}">
<link rel="stylesheet" href="{{ asset('css/course.css') }}">
<link rel="stylesheet" href="{{ asset('css/qbank.css') }}">
@endpush

@section('content')

<div class="courses-header calendar-header mb-4">

    <div class="calendar-nav">
        <a href="{{ route('calendar.index', ['date' => $startOfWeek->copy()->subWeek()->format('Y-m-d')]) }}"
            class="btn" aria-label="Previous week">
            <i class="bi bi-chevron-left"></i>
        </a>

        <h3 class="calendar-title mb-0">
            <span class="d-none d-sm-inline">{{ $startOfWeek->format('F Y') }}</span>
            <span class="d-sm-none">{{ $startOfWeek->format('M Y') }}</span>
        </h3>

        <a href="{{ route('calendar.index', ['date' => $startOfWeek->copy()->addWeek()->format('Y-m-d')]) }}"
            class="btn" aria-label="Next week">
            <i class="bi bi-chevron-right"></i>
        </a>
    </div>

    <div class="calendar-actions">
        <a href="{{ route('calendar.index') }}" class="btn btn-secondary-cal"
            title="Go to current week" aria-label="Go to current week">
            <svg xmlns="http://www.w3.org/2000/svg" width="1.5em" height="1.5em" viewBox="0 0 24 24">
                <path d="M0 0h24v24H0z" fill="none" />
                <path fill="currentColor" d="M17 3.34a10 10 0 1 1-14.995 8.984L2 12l.005-.324A10 10 0 0 1 17 3.34M12 6a1 1 0 0 0-.993.883L11 7v5l.009.131a1 1 0 0 0 .197.477l.087.1l3 3l.094.082a1 1 0 0 0 1.226 0l.094-.083l.083-.094a1 1 0 0 0 0-1.226l-.083-.094L13 11.585V7l-.007-.117A1 1 0 0 0 12 6" />
            </svg>
        </a>

        <button type="button" class="btn btn-theme add-event-btn" data-bs-toggle="modal" data-bs-target="#createEventModal"
            title="Add event" aria-label="Add event">
            <i class="bi bi-plus-circle"></i>
            <span class="ms-1">Add Event</span>
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
            <button type="button" class="week-add-btn" onclick="openCreateModal('{{ $day->format('Y-m-d') }}')"
                aria-label="Add event on {{ $day->format('M j') }}">
                <i class="bi bi-plus-circle"></i>
            </button>
        </div>

        <div class="week-event-list">

            @forelse($dayActivities as $activity)
            @php
            $typeClass = match($activity->type) {
                'Study Session' => 'type-study',
                'Quiz' => 'type-quiz',
                'Assignment' => 'type-assignment',
                'Meeting' => 'type-meeting',
                default => 'type-personal',
            };
            $statusClass = match($activity->display_status) {
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
                <div class="week-event-type">{{ $activity->type }}</div>
            </div>
            @empty
            <div class="week-empty-day">No events</div>
            @endforelse

        </div>

    </div>
    @endforeach

</div>

{{-- CREATE EVENT MODAL --}}
<div class="modal fade" id="createEventModal" tabindex="-1"
    data-has-errors="{{ $errors->any() ? '1' : '0' }}">
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
                        <input type="date" name="event_date" id="createEventDate" value="{{ old('event_date') }}"
                            class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="name" value="{{ old('name') }}" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Starts</label>
                            <input type="time" name="starts_time" id="createStartsTime" value="{{ old('starts_time') }}"
                                class="form-control" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Ends <small class="text-muted">(optional)</small></label>
                            <input type="time" name="ends_time" id="createEndsTime" value="{{ old('ends_time') }}"
                                class="form-control">
                        </div>
                    </div>

                    <div class="form-check mb-3">
                        <input type="hidden" name="is_all_day" value="0">
                        <input type="checkbox" class="form-check-input" id="isAllDayCheck" name="is_all_day" value="1"
                            {{ old('is_all_day') ? 'checked' : '' }}>
                        <label class="form-check-label" for="isAllDayCheck">All-day event</label>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Type</label>
                            <select name="type" id="eventTypeSelect" class="form-select" required>
                                @foreach(['Study Session', 'Meeting', 'Other'] as $type)
                                <option value="{{ $type }}" {{ old('type') === $type ? 'selected' : '' }}>{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mb-3 d-none" id="meetingLinkGroup">
                        <label class="form-label">Meeting Link <small class="text-muted">(optional)</small></label>
                        <input type="url" name="meeting_link" id="meetingLinkInput" value="{{ old('meeting_link') }}"
                            class="form-control" placeholder="https://..." disabled>
                    </div>

                    {{-- Only users who are admin/teacher of at least one class --}}
                    @if($managedClasses->isNotEmpty())
                    <div class="mb-1">
                        <label class="form-label">Share with classes <small class="text-muted">(optional)</small></label>

                        <div class="border rounded-3 px-3 py-2" style="max-height: 180px; overflow-y: auto;">
                            @foreach($managedClasses as $class)
                            <div class="form-check py-1">
                                <input class="form-check-input" type="checkbox" name="class_ids[]"
                                    value="{{ $class->id }}" id="shareClass{{ $class->id }}"
                                    {{ collect(old('class_ids', []))->contains($class->id) ? 'checked' : '' }}>
                                <label class="form-check-label" for="shareClass{{ $class->id }}">
                                    {{ $class->name }}
                                    <small class="text-muted">· {{ $class->course->name }}</small>
                                </label>
                            </div>
                            @endforeach
                        </div>

                        <div class="form-text">Everyone in the selected classes is added to this event.</div>
                    </div>
                    @endif

                </div>
                <div class="modal-footer border-0 pt-0">
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
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="eventDetailTitle">Event</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-3" id="eventDetailBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-danger"></div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

        const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (c) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[c]));

        // Only http(s) links are allowed; adds https:// when the scheme is missing
        function safeUrl(raw) {
            if (!raw) return null;
            const trimmed = String(raw).trim();
            const withScheme = /^[a-z][a-z0-9+.-]*:/i.test(trimmed) ? trimmed : 'https://' + trimmed;
            try {
                const url = new URL(withScheme);
                return ['http:', 'https:'].includes(url.protocol) ? url.href : null;
            } catch (e) {
                return null;
            }
        }

        function updateEventStatus(id, status) {
            const isCancel = status === 'cancel';

            fetch(`/calendar/events/${id}/${isCancel ? 'cancel' : 'status'}`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: isCancel ? null : JSON.stringify({ status }),
                })
                .then(function (r) {
                    if (!r.ok) throw new Error();
                    window.location.reload();
                })
                .catch(function () {
                    alert('Failed to update event status. Please try again.');
                });
        }

        // ---- Create modal ----
        const createModalEl = document.getElementById('createEventModal');
        const typeSelect = document.getElementById('eventTypeSelect');
        const meetingGroup = document.getElementById('meetingLinkGroup');
        const meetingInput = document.getElementById('meetingLinkInput');

        // set by Blade on the modal element, so no Blade code lives inside this script
        const hasFormErrors = createModalEl.dataset.hasErrors === '1';

        function toggleMeetingLink() {
            const isMeeting = typeSelect.value === 'Meeting';
            meetingGroup.classList.toggle('d-none', !isMeeting);
            meetingInput.disabled = !isMeeting;   // disabled inputs are not submitted
            if (!isMeeting) meetingInput.value = '';
        }

        typeSelect.addEventListener('change', toggleMeetingLink);
        toggleMeetingLink();

        window.openCreateModal = function (dateStr) {
            document.getElementById('createEventDate').value = dateStr;
            document.getElementById('createStartsTime').value = '08:00';
            document.getElementById('createEndsTime').value = '';
            bootstrap.Modal.getOrCreateInstance(createModalEl).show();
        };

        createModalEl.addEventListener('shown.bs.modal', function () {
            const dateField = document.getElementById('createEventDate');
            const startsTimeField = document.getElementById('createStartsTime');

            if (!dateField.value) {
                dateField.value = new Date().toISOString().split('T')[0];
            }
            if (!startsTimeField.value) {
                startsTimeField.value = '08:00';
            }
        });

        // ---- Detail modal ----
        function renderEventDetail(activity, body, title) {

            title.textContent = activity.name;

            const status = activity.my_status;
            const isSystemGenerated = !!activity.source_type;
            const startDate = new Date(activity.starts_at);
            const endDate = activity.ends_at ? new Date(activity.ends_at) : null;

            const dateLabel = startDate.toLocaleDateString(undefined, {
                weekday: 'long',
                day: 'numeric',
                month: 'long',
                year: 'numeric'
            });
            const timeOpts = { hour: '2-digit', minute: '2-digit' };
            const timeLabel = activity.is_all_day
                ? 'All day'
                : startDate.toLocaleTimeString(undefined, timeOpts)
                    + (endDate ? ' – ' + endDate.toLocaleTimeString(undefined, timeOpts) : '');

            const statusBadgeClass = {
                scheduled: 'bg-info text-dark',
                completed: 'bg-success',
                missed: 'bg-danger',
                cancelled: 'bg-secondary',
            }[status] || 'bg-secondary';

            const canChangeStatus = status === 'scheduled';
            const canCancel = !!activity.can_cancel;
            const meetingUrl = safeUrl(activity.meeting_link);
            const meetingHost = meetingUrl ? new URL(meetingUrl).hostname.replace(/^www\./, '') : null;

            const infoRow = (icon, content) => `
                <div class="d-flex align-items-start gap-3 mb-3">
                    <i class="bi ${icon} fs-5 text-muted flex-shrink-0" style="width: 1.5rem;"></i>
                    <div class="flex-grow-1" style="min-width: 0;">${content}</div>
                </div>`;

            body.innerHTML = `
                <div class="d-flex flex-wrap gap-2 mb-4">
                    ${activity.type ? `<span class="badge rounded-pill bg-dark">${escapeHtml(activity.type)}</span>` : ''}
                    <span class="badge rounded-pill text-capitalize ${statusBadgeClass}">${escapeHtml(status)}</span>
                </div>

                ${infoRow('bi-calendar-event', `
                    <div class="fw-semibold">${escapeHtml(dateLabel)}</div>
                    <div class="text-muted">${escapeHtml(timeLabel)}</div>
                `)}

                ${activity.source_url ? infoRow('bi-box-arrow-up-right', `
                    <a href="${escapeHtml(activity.source_url)}" class="fw-semibold text-decoration-none">
                        Open ${escapeHtml(activity.source_label)}
                    </a>
                    <div class="text-muted small">Linked to this event</div>
                `) : ''}

                ${meetingUrl ? infoRow('bi-camera-video', `
                    <div class="d-flex align-items-center justify-content-between gap-3">
                        <div class="text-truncate" style="min-width: 0;" title="${escapeHtml(meetingUrl)}">
                            <div class="fw-semibold">Online meeting</div>
                            <div class="text-muted small text-truncate">${escapeHtml(meetingHost)}</div>
                        </div>
                        <a href="${escapeHtml(meetingUrl)}" target="_blank" rel="noopener noreferrer"
                           class="btn btn-theme btn-sm flex-shrink-0">
                            Join
                        </a>
                    </div>
                `) : ''}

                ${activity.description ? infoRow('bi-text-paragraph', `
                    <div class="bg-light rounded-3 p-3" style="white-space: pre-line;">${escapeHtml(activity.description)}</div>
                `) : ''}

                ${isSystemGenerated ? `
                    <div class="alert alert-light border small mb-3">
                        This event is automatically managed and its schedule cannot be edited directly,
                        but you can still mark it complete or missed.
                    </div>
                ` : ''}

                ${canChangeStatus || canCancel ? `
                    <div class="d-flex flex-wrap gap-2 pt-3 border-top">
                        ${canChangeStatus ? `
                            <button type="button" class="btn btn-success flex-fill" id="completeEventBtn">
                                <i class="bi bi-check-circle"></i> Mark Complete
                            </button>
                            <button type="button" class="btn btn-secondary-custom flex-fill" id="missedEventBtn">
                                <i class="bi bi-x-circle"></i> Mark Missed
                            </button>
                        ` : ''}
                        ${canCancel ? `
                            <button type="button" class="btn btn-outline-danger w-100" id="cancelEventBtn">
                                <i class="bi bi-slash-circle"></i> Cancel Event for Everyone
                            </button>
                        ` : ''}
                    </div>
                ` : ''}
            `;

            const completeBtn = document.getElementById('completeEventBtn');
            if (completeBtn) {
                completeBtn.addEventListener('click', function () {
                    updateEventStatus(activity.id, 'completed');
                });
            }

            const missedBtn = document.getElementById('missedEventBtn');
            if (missedBtn) {
                missedBtn.addEventListener('click', function () {
                    if (confirm('Mark this event as missed?')) {
                        updateEventStatus(activity.id, 'missed');
                    }
                });
            }

            const cancelBtn = document.getElementById('cancelEventBtn');
            if (cancelBtn) {
                cancelBtn.addEventListener('click', function () {
                    if (confirm('Cancel this event for all participants?')) {
                        updateEventStatus(activity.id, 'cancel');
                    }
                });
            }
        }

        window.openEventDetail = function (activityId) {

            const modalEl = document.getElementById('eventDetailModal');
            const body = document.getElementById('eventDetailBody');
            const title = document.getElementById('eventDetailTitle');

            body.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-danger"></div></div>';
            title.textContent = 'Event';

            bootstrap.Modal.getOrCreateInstance(modalEl).show();

            fetch(`/calendar/events/${activityId}`, {
                    headers: { 'Accept': 'application/json' },
                })
                .then(function (r) {
                    if (!r.ok) throw new Error();
                    return r.json();
                })
                .then(function (activity) {
                    renderEventDetail(activity, body, title);
                })
                .catch(function () {
                    body.innerHTML = '<p class="text-danger">Failed to load event details.</p>';
                });
        };

        // Reopen the create modal when the form came back with validation errors
        if (hasFormErrors) {
            bootstrap.Modal.getOrCreateInstance(createModalEl).show();
        }

    });
</script>
@endpush

@endsection