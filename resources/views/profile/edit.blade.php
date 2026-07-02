@extends('layouts.app')

@section('title', 'Edit Profile')

@section('page-title', 'Edit Profile')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/profile.css') }}">
@endpush

@section('content')

<div class="row justify-content-center">

    <div class="col-lg-8">

        <div class="profile-card">

            {{-- Header --}}
            <div class="profile-header">

                <div class="profile-image-wrapper">

                    @if(auth()->user()->img)
                        <img src="{{ asset('storage/' . auth()->user()->img) }}"
                             alt="Profile Image"
                             class="profile-image">
                    @else
                        <img src="https://ui-avatars.com/api/?name={{ auth()->user()->name }}"
                             alt="Profile Image"
                             class="profile-image">
                    @endif

                </div>

                <div>

                    <h3 class="mb-1">
                        {{ auth()->user()->name }}
                    </h3>

                    <span class="badge bg-danger">
                        {{ auth()->user()->role }}
                    </span>

                </div>

            </div>

            {{-- Form --}}
            <form action="#"
                  method="POST"
                  enctype="multipart/form-data">

                @csrf
                @method('PUT')

                <div class="row">

                    {{-- Name --}}
                    <div class="col-md-6 mb-4">

                        <label class="form-label">
                            Full Name
                        </label>

                        <input type="text"
                               name="name"
                               class="form-control"
                               value="{{ old('name', auth()->user()->name) }}"
                               required>

                    </div>

                    {{-- Email --}}
                    <div class="col-md-6 mb-4">

                        <label class="form-label">
                            Email Address
                        </label>

                        <input type="email"
                               name="email"
                               class="form-control"
                               value="{{ old('email', auth()->user()->email) }}"
                               required>

                    </div>

                    {{-- Role --}}
                    <div class="col-md-6 mb-4">

                        <label class="form-label">
                            Role
                        </label>

                        <input type="text"
                               class="form-control"
                               value="{{ auth()->user()->role }}"
                               disabled>

                    </div>

                    {{-- Status --}}
                    <div class="col-md-6 mb-4">

                        <label class="form-label">
                            Status
                        </label>

                        <input type="text"
                               class="form-control"
                               value="{{ auth()->user()->is_active ? 'Active' : 'Inactive' }}"
                               disabled>

                    </div>

                    {{-- Profile Image --}}
                    <div class="col-12 mb-4">

                        <label class="form-label">
                            Profile Image
                        </label>

                        <input type="file"
                               name="img"
                               class="form-control">

                    </div>

                    {{-- Password --}}
                    <div class="col-md-6 mb-4">

                        <label class="form-label">
                            New Password
                        </label>

                        <input type="password"
                               name="password"
                               class="form-control">

                    </div>

                    {{-- Confirm Password --}}
                    <div class="col-md-6 mb-4">

                        <label class="form-label">
                            Confirm Password
                        </label>

                        <input type="password"
                               name="password_confirmation"
                               class="form-control">

                    </div>

                </div>

                <div class="d-flex justify-content-end gap-3">

                    <a href="{{ url()->previous() }}"
                       class="btn btn-secondary-custom">
                        Cancel
                    </a>

                    <button type="submit"
                            class="btn btn-theme">
                        <i class="bi bi-check-circle"></i>
                        Save Changes
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

@endsection