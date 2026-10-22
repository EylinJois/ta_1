@extends('layouts.app')

@section('title', 'Edit Course')

@section('page-title', 'Edit Course')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/course.css') }}">
@endpush

@section('content')

<div class="row justify-content-center">

    <div class="col-lg-9">

        <div class="form-card">

            <div class="form-card-header">

                <div>

                    <h3>
                        Edit Course
                    </h3>

                    <p class="text-muted mb-0">
                        Update course information.
                    </p>

                </div>

                <div class="header-icon">
                    <i class="bi bi-pencil-square"></i>
                </div>

            </div>

            <form action="{{ route('courses.update', $course->id) }}"
                method="POST"
                enctype="multipart/form-data">

                @csrf
                @method('PUT')

                <div class="row">

                    {{-- Name --}}
                    <div class="col-12 mb-4">

                        <label class="form-label">
                            Course Name
                        </label>

                        <input type="text"
                            name="name"
                            class="form-control"
                            value="{{ old('name', $course->name) }}">

                    </div>

                    {{-- Image --}}
                    <div class="col-12 mb-4">

                        <label class="form-label">
                            Course Thumbnail
                        </label>

                        <input type="file"
                            name="img"
                            class="form-control"
                            id="imageInput">

                    </div>

                    {{-- Image Preview --}}
                    <div class="col-12 mb-4">

                        <div class="image-preview-container">

                            @if($course->imgMedia)
    <img src="{{ Storage::disk($course->imgMedia->storage_disk)->url($course->imgMedia->file_path) }}" class="course-image">


                            @else

                            <img src="https://placehold.co/1200x400?text=No+Image"
                                id="previewImage"
                                class="edit-course-image">

                            @endif

                        </div>

                    </div>

                    {{-- Description --}}
                    <div class="col-12 mb-4">

                        <label class="form-label">
                            Description
                        </label>

                        <textarea name="description"
                            rows="6"
                            class="form-control">{{ old('description', $course->description) }}</textarea>

                    </div>

                </div>

                <div class="form-actions">

                    <a href="{{ route('courses.show', $course->id) }}"
                        class="btn btn-secondary-custom">

                        Back

                    </a>

                    <button type="submit"
                        class="btn btn-theme">

                        <i class="bi bi-check-circle-fill"></i>
                        Update Course

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

@push('scripts')

<script>

    const imageInput = document.getElementById('imageInput');

    const previewImage = document.getElementById('previewImage');

    imageInput.addEventListener('change', function(e){

        const file = e.target.files[0];

        if(file){

            const reader = new FileReader();

            reader.onload = function(event){

                previewImage.src = event.target.result;

            }

            reader.readAsDataURL(file);

        }

    });

</script>

@endpush

@endsection