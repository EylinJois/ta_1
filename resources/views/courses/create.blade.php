@extends('layouts.app')

@section('title', 'Create Course')

@section('page-title', 'Create Course')

@push('styles')

    <link rel="stylesheet"
          href="{{ asset('css/course.css') }}">

@endpush

@section('content')

<div class="row justify-content-center">

    <div class="col-lg-9">

        <div class="form-card">

            {{-- Header --}}
            <div class="form-card-header">

                <div>

                    <h3>
                        Create New Course
                    </h3>

                    <p class="text-muted mb-0">
                        Add a new Mandarin learning course.
                    </p>

                </div>

                <div class="header-icon">
                    <i class="bi bi-journal-plus"></i>
                </div>

            </div>

            {{-- Form --}}
            <form action="{{ route('courses.store') }}"
                  method="POST"
                  enctype="multipart/form-data">

                @csrf

                <div class="row">

                    {{-- Course Name --}}
                    <div class="col-12 mb-4">

                        <label class="form-label">
                            Course Name
                        </label>

                        <input type="text"
                               name="name"
                               class="form-control @error('name') is-invalid @enderror"
                               placeholder="Enter course name"
                               value="{{ old('name') }}">

                        @error('name')

                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>

                        @enderror

                    </div>

                    {{-- Course Image --}}
                    <div class="col-12 mb-4">

                        <label class="form-label">
                            Course Thumbnail
                        </label>

                        <input type="file"
                               name="img"
                               class="form-control @error('img') is-invalid @enderror"
                               id="imageInput">

                        @error('img')

                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>

                        @enderror

                    </div>

                    {{-- Preview --}}
                    <div class="col-12 mb-4">

                        <div class="image-preview"
                             id="imagePreview">

                            <div class="preview-placeholder">

                                <i class="bi bi-image-fill"></i>

                                <p>
                                    Image Preview
                                </p>

                            </div>

                        </div>

                    </div>

                    {{-- Description --}}
                    <div class="col-12 mb-4">

                        <label class="form-label">
                            Course Description
                        </label>

                        <textarea name="description"
                                  rows="6"
                                  class="form-control @error('description') is-invalid @enderror"
                                  placeholder="Write course description...">{{ old('description') }}</textarea>

                        @error('description')

                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>

                        @enderror

                    </div>
                </div>

                {{-- Actions --}}
                <div class="form-actions">

                    <a href="{{ route('courses.index') }}"
                       class="btn btn-secondary-custom">

                        <i class="bi bi-arrow-left"></i>
                        Back

                    </a>

                    <button type="submit"
                            class="btn btn-theme">

                        <i class="bi bi-check-circle-fill"></i>
                        Create Course

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

@endsection

@push('scripts')

<script>

    const imageInput = document.getElementById('imageInput');
    const imagePreview = document.getElementById('imagePreview');

    imageInput.addEventListener('change', function(e){

        const file = e.target.files[0];

        if(file){

            const reader = new FileReader();

            reader.onload = function(event){

                imagePreview.innerHTML = `
                    <img src="${event.target.result}"
                         class="preview-image">
                `;

            }

            reader.readAsDataURL(file);

        }

    });

</script>

@endpush