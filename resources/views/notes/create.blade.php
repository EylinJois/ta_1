@extends('layouts.app')

@section('title', 'New Note')

@section('page-title', 'New Note')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/notes.css') }}">

@endpush

@section('content')

<div class="container-fluid">

    <div class="table-card">

        <form action="{{ route('notes.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="courses-header mb-4">
                <a href="{{ route('notes.index', $folder ? ['folder' => $folder->id] : []) }}"
                    class="btn btn-secondary-custom">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h4 class="mb-0">New Note</h4>
            </div>

            <div class="row">

                {{-- MAIN --}}
                <div class="col-lg-8">

                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control"
                            placeholder="e.g. Chapter 3 - Greetings" value="{{ old('title') }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Content</label>
                        <textarea name="content" class="form-control tinymce-editor" rows="10"
                            placeholder="Write your note here...">{{ old('content') }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            Photos of Physical Notes (max. 10 MB)
                        </label>
                        <input type="file" name="photos[]" id="photoInput" class="form-control"
                            accept="image/*" multiple>
                        <div class="form-text">
                            Snap a photo of handwritten notes and attach them here — you can select multiple. Click a photo to view it larger.
                        </div>
                        <div id="photoPreviewGrid" class="photo-preview-grid mt-3"></div>
                    </div>

                </div>

                {{-- SIDEBAR --}}
                <div class="col-lg-4">

                    <div class="note-form-sidebar">

                        <div class="mb-3">
                            <label class="form-label">Folder</label>
                            <select name="folder_id" class="form-select">
                                <option value="">No Folder</option>
                                @foreach($allFolders as $f)
                                <option value="{{ $f->id }}" {{ (old('folder_id', $folder->id ?? null) == $f->id) ? 'selected' : '' }}>
                                    {{ $f->breadcrumb ?? $f->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-check mb-4">
                            <input type="checkbox" class="form-check-input" id="pinnedCheck" name="pinned" value="1"
                                {{ old('pinned') ? 'checked' : '' }}>
                            <label class="form-check-label" for="pinnedCheck">
                                <i class="bi bi-pin-angle-fill"></i>
                                Pin this note for quick access
                            </label>
                        </div>

                        <div class="mb-2">
                            <label class="form-label">
                                Link to Materials
                                <span class="text-muted small">(optional)</span>
                            </label>
                        </div>

                        <div class="note-material-picker">
                            @forelse($materials as $material)
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input"
                                    name="material_ids[]" value="{{ $material->id }}"
                                    id="material{{ $material->id }}"
                                    {{ in_array($material->id, old('material_ids', [])) ? 'checked' : '' }}>
                                <label class="form-check-label" for="material{{ $material->id }}">
                                    {{ $material->name }}
                                </label>
                            </div>
                            @empty
                            <p class="text-muted small mb-0">No materials available to link.</p>
                            @endforelse
                        </div>

                    </div>

                </div>

            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="{{ route('notes.index', $folder ? ['folder' => $folder->id] : []) }}"
                    class="btn btn-secondary-custom">
                    Cancel
                </a>
                <button type="submit" class="btn btn-theme">
                    <i class="bi bi-check-circle"></i>
                    Save Note
                </button>
            </div>

        </form>

    </div>

</div>

{{-- LIGHTBOX (populated by JS on click) --}}
<div class="photo-lightbox-overlay d-none" id="photoLightbox">
    <button type="button" class="photo-lightbox-close" id="photoLightboxClose">&times;</button>
    <button type="button" class="photo-lightbox-nav photo-lightbox-prev" id="photoLightboxPrev">
        <i class="bi bi-chevron-left"></i>
    </button>
    <img src="" alt="" id="photoLightboxImg">
    <button type="button" class="photo-lightbox-nav photo-lightbox-next" id="photoLightboxNext">
        <i class="bi bi-chevron-right"></i>
    </button>
    <div class="photo-lightbox-counter" id="photoLightboxCounter"></div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    /* ===================== TINYMCE ===================== */

    if (typeof tinymce !== 'undefined') {
        tinymce.init({
            selector: '.tinymce-editor',
            height: 350,
            menubar: false,
            plugins: 'lists link',
            toolbar: 'bold italic underline | bullist numlist | link',
        });
    }

    /* ===================== PHOTO PREVIEW + LIGHTBOX + ACCUMULATION ===================== */

    const photoInput = document.getElementById('photoInput');
    const previewGrid = document.getElementById('photoPreviewGrid');
    const lightbox = document.getElementById('photoLightbox');
    const lightboxImg = document.getElementById('photoLightboxImg');
    const lightboxClose = document.getElementById('photoLightboxClose');
    const lightboxPrev = document.getElementById('photoLightboxPrev');
    const lightboxNext = document.getElementById('photoLightboxNext');
    const lightboxCounter = document.getElementById('photoLightboxCounter');
    const form = photoInput.closest('form');

    let selectedFiles = [];
    let previewUrls = []; // cached data: URLs, index-aligned with selectedFiles
    let currentLightboxIndex = 0;

    let hiddenPhotoInput = document.createElement('input');
    hiddenPhotoInput.type = 'file';
    hiddenPhotoInput.name = 'photos[]';
    hiddenPhotoInput.multiple = true;
    hiddenPhotoInput.style.display = 'none';
    form.appendChild(hiddenPhotoInput);

    function syncHiddenInput() {
        const dataTransfer = new DataTransfer();
        selectedFiles.forEach(function (file) {
            dataTransfer.items.add(file);
        });
        hiddenPhotoInput.files = dataTransfer.files;
    }

    function renderPreviews() {
        previewGrid.innerHTML = '';

        selectedFiles.forEach(function (file, index) {

            if (!previewUrls[index]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    previewUrls[index] = e.target.result;
                    renderThumb(file, index);
                };
                reader.readAsDataURL(file);
            } else {
                renderThumb(file, index);
            }
        });
    }

    function renderThumb(file, index) {
        const thumb = document.createElement('div');
        thumb.className = 'photo-preview-thumb';
        thumb.innerHTML = `
            <img src="${previewUrls[index]}" alt="${file.name}">
            <button type="button" class="photo-preview-remove" data-index="${index}" title="Remove">
                &times;
            </button>
        `;

        thumb.querySelector('img').addEventListener('click', function () {
            openLightbox(index);
        });

        thumb.querySelector('.photo-preview-remove').addEventListener('click', function (evt) {
            evt.stopPropagation();
            selectedFiles.splice(index, 1);
            previewUrls.splice(index, 1);
            syncHiddenInput();
            renderPreviews();
        });

        previewGrid.appendChild(thumb);
    }

    /* ===================== LIGHTBOX WITH NAVIGATION ===================== */

    function openLightbox(index) {
        currentLightboxIndex = index;
        showLightboxImage();
        lightbox.classList.remove('d-none');
    }

    function showLightboxImage() {
        const file = selectedFiles[currentLightboxIndex];
        lightboxImg.src = previewUrls[currentLightboxIndex];
        lightboxImg.alt = file ? file.name : '';

        lightboxCounter.textContent = `${currentLightboxIndex + 1} / ${selectedFiles.length}`;

        lightboxPrev.disabled = currentLightboxIndex === 0;
        lightboxNext.disabled = currentLightboxIndex === selectedFiles.length - 1;
    }

    function showPrev() {
        if (currentLightboxIndex > 0) {
            currentLightboxIndex--;
            showLightboxImage();
        }
    }

    function showNext() {
        if (currentLightboxIndex < selectedFiles.length - 1) {
            currentLightboxIndex++;
            showLightboxImage();
        }
    }

    function closeLightbox() {
        lightbox.classList.add('d-none');
        lightboxImg.src = '';
    }

    lightboxClose.addEventListener('click', closeLightbox);
    lightboxPrev.addEventListener('click', showPrev);
    lightboxNext.addEventListener('click', showNext);

    lightbox.addEventListener('click', function (e) {
        if (e.target === lightbox) closeLightbox();
    });

    document.addEventListener('keydown', function (e) {
        if (lightbox.classList.contains('d-none')) return;
        if (e.key === 'Escape') closeLightbox();
        if (e.key === 'ArrowLeft') showPrev();
        if (e.key === 'ArrowRight') showNext();
    });

    /* ===================== FILE SELECTION ===================== */

    photoInput.addEventListener('change', function () {
        selectedFiles = selectedFiles.concat(Array.from(photoInput.files));
        photoInput.value = '';

        syncHiddenInput();
        renderPreviews();
    });

});
</script>
@endpush
@endsection