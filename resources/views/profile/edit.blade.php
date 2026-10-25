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

        
            @session('success')
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endsession

            {{-- Header --}}
            <div class="profile-header">

                <div class="profile-image-wrapper">

                    <label for="img" class="profile-image-label" title="Change profile picture">

                        @if(auth()->user()->img)
                        <img src="{{ asset('storage/' . auth()->user()->profileMedia()->file_path) }}"
                            alt="Profile Image"
                            class="profile-image"
                            id="profilePreview">
                        @else
                        <img src="https://ui-avatars.com/api/?name={{ auth()->user()->name }}"
                            alt="Profile Image"
                            class="profile-image"
                            id="profilePreview">
                        @endif

                        <span class="profile-image-overlay">
                            <i class="bi bi-camera"></i>
                        </span>

                    </label>

                </div>

                <div>

                    <h3 class="mb-1">
                        {{ auth()->user()->name }}
                    </h3>

                </div>

            </div>

            {{-- Form --}}
            <form action="{{ route('profile.update', auth()->user()->id) }}"
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

                    {{-- Profile Image --}}
                    <div class="col-12 mb-4">
                        <label class="form-label">
                            Profile Image
                        </label>
                        <input type="file" name="profile_media" id="img"
                        class="form-control" accept="image/*">
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

                    <a href="{{ back()->getTargetUrl() }}"
                        class="btn btn-secondary-custom btn-sm">
                        Cancel
                    </a>

                    <button type="submit"
                        class="btn btn-theme">
                        <i class="bi bi-check-circle btn-sm"></i>
                        Save Changes
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

@push('scripts')
<script>
    document.getElementById('img').addEventListener('change', function (e) {
        const file = e.target.files[0];
        if (file) {
            document.getElementById('profilePreview').src = URL.createObjectURL(file);
        }
    });
</script>
@endpush

@endsection