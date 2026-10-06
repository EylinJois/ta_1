@extends('layouts.app')

@section('title', $course->name)

@section('page-title', 'Course Detail')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/course.css') }}">
@endpush

@section('content')
<div class="table-card">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="{{ route('courses.show', $course->id) }}" class="btn btn-secondary-custom btn-icon-plain">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h4 class="section-title mb-0">{{ $course->name }} Participants</h4>
        @if($isAdmin)
        <div class="d-flex gap-2">
            <button class="btn btn-secondary-custom" data-bs-toggle="modal" data-bs-target="#classModal">
                <i class="bi bi-diagram-3"></i>
                Add Class
            </button>

            <button class="btn btn-theme" data-bs-toggle="modal" data-bs-target="#participantModal">
                <i class="bi bi-plus-circle"></i>
                Add Participant
            </button>
        </div>
        @endif
    </div>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>Class</th>
                    <th>Role</th>
                    @if($canManage)
                    <th class="text-center">Actions</th>
                    @endif
                </tr>
            </thead>

            <tbody>
                @forelse($classes as $class)

                {{-- Group header --}}
                <tr>
                    <td colspan="{{ $colspan }}" class="fw-bold bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>
                                <i class="bi bi-people-fill me-1"></i>
                                {{ $class->name }}
                                <span class="badge bg-secondary ms-2">{{ $class->users->count() }}</span>
                            </span>

                            @if($isAdmin)
                            <span class="d-flex gap-1">
                                {{-- Rename --}}
                                <button type="button"
                                    data-bs-toggle="modal"
                                    data-bs-target="#renameClassModal{{ $class->id }}"
                                    class="icon-action-btn edit-btn"
                                    title="Rename Class">
                                    <i class="bi bi-pencil-square"></i>
                                </button>

                                {{-- Delete --}}
                                <form action="{{ route('courses.classes.destroy', [
                        'id' => $course->id,
                        'classId' => $class->id,
                    ]) }}"
                                    method="POST"
                                    class="d-inline"
                                    onsubmit="return confirm({{ Js::from('Delete class "' . $class->name . '"? All ' . $class->users->count() . ' member(s) will be removed from this class and the course.') }})">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="icon-action-btn delete-btn" title="Delete Class">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </form>
                            </span>
                            @endif
                        </div>
                    </td>
                </tr>

                @forelse($class->users as $participant)
                <tr class="participant-row"
                    data-class-id="{{ $class->id }}">

                    <td data-label="User">
                        <div class="participant-user d-flex align-items-center gap-2">
                            @if(!empty($participant->img))
                            <img src="{{ asset('storage/' . $participant->img) }}" width="40" height="40" class="rounded-circle">
                            @else
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($participant->name ?? 'User') }}"
                                width="40" height="40" class="rounded-circle">
                            @endif
                            <div><strong>{{ $participant->name ?? '-' }}</strong></div>
                        </div>
                    </td>

                    <td data-label="Email">{{ $participant->email ?? '-' }}</td>

                    <td data-label="Class">{{ $class->name }}</td>

                    <td data-label="Role">
                        <span class="badge bg-primary">{{ $participant->pivot->role ?? '-' }}</span>
                    </td>

                    @if($canManage)
                    <td class="text-center actions-cell">
                        @if($isAdmin)
                        <button data-bs-toggle="modal"
                            data-bs-target="#editParticipantModal{{ $class->id }}-{{ $participant->id }}"
                            class="icon-action-btn edit-btn" title="Edit Participant">
                            <i class="bi bi-pencil-square"></i>
                        </button>

                        <form action="{{ route('courses.participants.destroy', [
                                'id' => $course->id,
                                'participantId' => $participant->id,
                            ]) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="class_id" value="{{ $class->id }}">
                            <button type="submit" class="icon-action-btn delete-btn" title="Delete Participant"
                                onclick="return confirm('Remove this participant from {{ e($class->name) }}?')">
                                <i class="bi bi-trash-fill"></i>
                            </button>
                        </form>
                        @endif

                        <a href="{{ route('progress.course', ['course' => $course->id, 'studentId' => $participant->id]) }}"
                            class="icon-action-btn edit-btn" title="View Progress">
                            <i class="bi bi-bar-chart-line"></i>
                        </a>
                    </td>
                    @endif
                </tr>
                @empty
                <tr class="class-empty-row">
                    <td colspan="{{ $colspan }}" class="text-center text-muted py-3">
                        No participants in this class.
                    </td>
                </tr>
                @endforelse

                @empty
                <tr>
                    <td colspan="{{ $colspan }}" class="text-center py-5">No classes found for this course.</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <span class="badge bg-danger">{{ $total }} users in course</span>
        </div>
    </div>
</div>

{{-- Add Participant Modal --}}
<div class="modal fade"
    id="participantModal"
    tabindex="-1">

    <div class="modal-dialog modal-lg modal-dialog-centered">

        <div class="modal-content custom-modal">

            <div class="modal-header border-0">

                <h4 class="modal-title">
                    Add Participants
                </h4>

                <button type="button"
                    class="btn-close"
                    data-bs-dismiss="modal">
                </button>

            </div>

            <form action="{{ route('courses.participants.store', $course->id) }}"
                method="POST">

                @csrf

                <div class="modal-body">

                    {{-- User --}}
                    <div class="mb-4">

                        <label class="form-label">
                            Select User
                        </label>

                        <select name="user_id" id="userSelect" class="form-select" required>
                            <option value="">Choose User</option>
                            @if(isset($users) && $users->count() > 0)
                            @foreach($users as $user)
                            <option value="{{ $user->id }}">
                                {{ $user->name ?? '-' }} ({{ $user->email ?? '-' }})
                            </option>
                            @endforeach
                            @endif
                        </select>

                    </div>

                    {{-- Role --}}
                    <div class="mb-4">

                        <label class="form-label">
                            Role in Class
                        </label>

                        <select name="role"
                            class="form-select"
                            required>

                            <option value="admin">
                                Admin
                            </option>

                            <option value="teacher">
                                Teacher
                            </option>

                            <option value="student">
                                Student
                            </option>

                        </select>

                    </div>

                    <div class="mb-4">
                        <label class="form-label">Class</label>
                        <select name="class_id" class="form-select" required>
                            <option value="">Choose Class</option>
                            @foreach($classes as $class)
                            <option value="{{ $class->id }}">{{ $class->name }}</option>
                            @endforeach
                        </select>
                    </div>

                </div>

                <div class="modal-footer border-0">

                    <button type="button"
                        class="btn btn-secondary-custom"
                        data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit"
                        class="btn btn-theme">
                        <i class="bi bi-check-circle"></i>
                        Add Participant
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Add Class Modal --}}
@if($isAdmin)
<div class="modal fade" id="classModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-modal">

            <form action="{{ route('courses.classes.store', $course->id) }}" method="POST">
                @csrf
                <input type="hidden" name="course_id" value="{{ $course->id }}">

                <div class="modal-header border-0">
                    <div>
                        <h5 class="modal-title fw-bold">Add Class</h5>
                        <small class="text-muted">Create a new class in {{ $course->name }}</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <label for="className" class="form-label fw-semibold">Class Name</label>
                    <input type="text"
                        id="className"
                        name="name"
                        value="{{ old('name') }}"
                        class="form-control @error('name') is-invalid @enderror"
                        maxlength="255"
                        placeholder="e.g. Class A"
                        required>
                    @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-theme">
                        <i class="bi bi-check-circle"></i>
                        Add Class
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>
@endif

{{-- Rename Edit Class Modal --}}
@if($isAdmin)
@foreach($classes as $class)
@php $hasRenameError = $errors->renameClass->any() && (int) old('rename_class_id') === $class->id; @endphp
<div class="modal fade" id="renameClassModal{{ $class->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-modal">
            <form action="{{ route('courses.classes.update', [
                    'id' => $course->id,
                    'classId' => $class->id,
                ]) }}" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="rename_class_id" value="{{ $class->id }}">

                <div class="modal-header border-0">
                    <div>
                        <h5 class="modal-title fw-bold">Rename Class</h5>
                        <small class="text-muted">{{ $class->name }}</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <label class="form-label fw-semibold">Class Name</label>
                    <input type="text"
                        name="name"
                        value="{{ $hasRenameError ? old('name') : $class->name }}"
                        class="form-control {{ $hasRenameError ? 'is-invalid' : '' }}"
                        maxlength="255"
                        required>
                    @if($hasRenameError)
                    <div class="invalid-feedback">{{ $errors->renameClass->first('name') }}</div>
                    @endif
                </div>

                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-theme">
                        <i class="bi bi-check-circle-fill"></i> Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endif

@if($isAdmin)
@foreach($classes as $class)
@foreach($class->users as $participant)
<div class="modal fade" id="editParticipantModal{{ $class->id }}-{{ $participant->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-modal">
            <form action="{{ route('courses.participants.update', [
                    'id' => $course->id,
                    'participantId' => $participant->id,
                ]) }}" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="old_class_id" value="{{ $class->id }}">

                <div class="modal-header border-0">
                    <div>
                        <h5 class="modal-title fw-bold">Edit Participant</h5>
                        <small class="text-muted">{{ $participant->name }}</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Class</label>
                        <select name="class_id" class="form-select" required>
                            @foreach($classes as $classOption)
                            <option value="{{ $classOption->id }}"
                                {{ $classOption->id === $class->id ? 'selected' : '' }}>
                                {{ $classOption->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-semibold">Role</label>
                        <select name="role" class="form-select" required>
                            @foreach(['admin' => 'Admin', 'teacher' => 'Teacher', 'student' => 'Student'] as $role => $label)
                            <option value="{{ $role }}"
                                {{ strtolower($participant->pivot->role) === $role ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-theme">
                        <i class="bi bi-check-circle-fill"></i> Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endforeach
@endif

@push('scripts')
@if($errors->has('name'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        new bootstrap.Modal(document.getElementById('classModal')).show();
    });
</script>
@endif
@if($errors->renameClass->any() && old('rename_class_id'))
<script>
    document.addEventListener('DOMContentLoaded', function () {
        new bootstrap.Modal(document.getElementById('renameClassModal{{ (int) old('rename_class_id') }}')).show();
    });
</script>
@endif
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        $('#userSelect').select2({
            dropdownParent: $('#participantModal'),
            width: '100%',
            placeholder: 'Choose User',
            allowClear: true
        });
    });
</script>
@endpush
@endsection