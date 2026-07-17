@extends('layouts.app')

@section('title', $course->name)

@section('page-title', 'Course Detail')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/course.css') }}">
@endpush

@section('content')
{{-- Participants --}}
<div class="table-card">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <h4 class="section-title mb-0">
            {{$course->name}} Participants
        </h4>
@if(Auth::user()->isAdmin($course->id))
        <div>

            <button class="btn btn-theme"
                data-bs-toggle="modal"
                data-bs-target="#participantModal">

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
                    <th>Role</th>
                    @if(Auth::user()->isAdmin($course->id))
                    <th class="text-center">Actions</th>
                    @endif
                </tr>

            </thead>

            <tbody>

                @if($course->users && $course->users->count() > 0)

                @foreach($course->users as $participant)

                <tr>

                    <td>

                        <div class="participant-user d-flex align-items-center gap-2">

                            @if(!empty($participant->img))

                            <img src="{{ asset('storage/' . $participant->img) }}"
                                width="40"
                                height="40"
                                class="rounded-circle">

                            @else

                            <img src="https://ui-avatars.com/api/?name={{ urlencode($participant->name ?? 'User') }}"
                                width="40"
                                height="40"
                                class="rounded-circle">

                            @endif

                            <div>

                                <strong>
                                    {{ $participant->name ?? '-' }}
                                </strong>

                            </div>

                        </div>

                    </td>

                    <td>
                        {{ $participant->email ?? '-' }}
                    </td>

                    <td>

                        <span class="badge bg-primary">

                            {{ $participant->pivot->role ?? '-' }}

                        </span>

                    </td>
@if(Auth::user()->isAdmin($course->id))
                    <td class="text-center">

                        {{-- Edit Button --}}
                        <button
                            data-bs-toggle="modal"
                            data-bs-target="#editParticipantModal{{ $participant->id }}"
                            class="icon-action-btn edit-btn"
                            title="Edit Participant">

                            <i class="bi bi-pencil-square"></i>

                        </button>

                        {{-- Delete Button --}}
                        <form action="{{ route('courses.participants.destroy', ['id' => $course->id, 'participantId' => $participant->id]) }}"
                            method="POST"
                            class="d-inline">

                            @csrf
                            @method('DELETE')

                            <button type="submit"
                                class="icon-action-btn delete-btn"
                                title="Delete Participant"
                                onclick="return confirm('Delete this participant?')">

                                <i class="bi bi-trash-fill"></i>

                            </button>

                        </form>

                    </td>
@endif
                </tr>

                @endforeach

                @else

                <tr>

                    <td colspan="4"
                        class="text-center py-5">

                        No participants added.

                    </td>

                </tr>

                @endif

            </tbody>

        </table>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="{{ route('courses.show', $course->id) }}"
                class="btn btn-secondary-custom">
                <i class="bi bi-arrow-left"></i>
                Back
            </a>
            <span class="badge bg-danger">

                {{ $course->users ? $course->users->count() : 0 }}
                Participants

            </span>
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

                        <select name="user_id"
                            class="form-select"
                            required>

                            <option value="">
                                Choose User
                            </option>

                            @if(isset($users) && $users->count() > 0)

                            @foreach($users as $user)

                            <option value="{{ $user->id }}">

                                {{ $user->name ?? '-' }}
                                ({{ $user->email ?? '-' }})

                            </option>

                            @endforeach

                            @endif

                        </select>

                    </div>

                    {{-- Role --}}
                    <div class="mb-4">

                        <label class="form-label">
                            Role in Course
                        </label>

                        <select name="role"
                            class="form-select"
                            required>

                            <option value="Admin">
                                Admin
                            </option>

                            <option value="Teacher">
                                Teacher
                            </option>

                            <option value="Student">
                                Student
                            </option>

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

@if (isset($participant))

{{-- EDIT SECTION MODAL --}}
<div class="modal fade"

     id="editParticipantModal{{ $participant->id }}"
     tabindex="-1"
     aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content custom-modal">

            <form action="{{ route('courses.participants.update',['id' => $course->id, 'participantId' => $participant->id]) }}"
                  method="POST">

                @csrf
                @method('PUT')

                {{-- Header --}}
                <div class="modal-header border-0">

                    <div>

                        <h5 class="modal-title fw-bold">
                            Edit Participant
                        </h5>

                        <small class="text-muted">
                            Update participant information
                        </small>

                    </div>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal">
                    </button>

                </div>

                {{-- Body --}}
                <div class="modal-body">

                    {{-- Name --}}
                    <div class="mb-4">

                        <label class="form-label fw-semibold">
                            Participant Name
                        </label>

                        <input type="text"
                               name="name"
                               class="form-control"
                               value="{{ old('name', $participant->name) }}"
                               required>

                    </div>

                    {{-- Visibility --}}
                    <div class="mb-2">

                        <label class="form-label fw-semibold">
                            Role
                        </label>

                        <select name="role"
                                class="form-select"
                                required>
                            <option value="Admin" {{ $participant->pivot->role === 'admin' ? 'selected' : '' }}>
                                Admin
                            </option>
                            <option value="Teacher" {{ $participant->pivot->role === 'teacher' ? 'selected' : '' }}>
                                Teacher
                            </option>
                            <option value="Student" {{ $participant->pivot->role === 'student' ? 'selected' : '' }}>
                                Student
                            </option>

                        </select>

                    </div>

                </div>

                {{-- Footer --}}
                <div class="modal-footer border-0">

                    <button type="button"
                            class="btn btn-light"
                            data-bs-dismiss="modal">

                        Cancel

                    </button>

                    <button type="submit"
                            class="btn btn-theme">

                        <i class="bi bi-check-circle-fill"></i>
                        Update

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>
@else
@endif

@endsection