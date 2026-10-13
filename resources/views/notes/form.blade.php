@extends('layouts.app')

@php $isEdit = $note->exists; @endphp

@section('title', $isEdit ? 'Edit Note' : 'New Note')

@section('page-title', $isEdit ? 'Edit Note' : 'New Note')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/notes.css') }}">
<style>
    .note-form-layout .note-form-right {
        display: flex;
        flex-direction: column;
        height: 100%;
    }

    .note-form-layout .photo-preview-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
    }

    .note-form-layout .photo-preview-grid:empty {
        display: none;
    }

    .note-form-layout .photo-preview-grid + .photo-preview-grid {
        margin-top: 12px;
    }

    .note-form-layout .photo-preview-thumb {
        position: relative;
        aspect-ratio: 1 / 1;
        border-radius: 8px;
        overflow: hidden;
        background: #d9d9d9;
    }

    .note-form-layout .photo-preview-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        cursor: pointer;
        display: block;
    }

    .note-form-layout .photo-preview-missing {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: #666;
    }

    .note-form-layout .photo-preview-remove {
        position: absolute;
        top: 4px;
        right: 4px;
        width: 22px;
        height: 22px;
        line-height: 20px;
        padding: 0;
        border: 0;
        border-radius: 50%;
        background: rgba(60, 60, 60, .85);
        color: #fff;
        font-size: 14px;
        cursor: pointer;
    }

    .note-form-layout .note-form-actions {
        margin-top: auto;
        padding-top: 24px;
    }

    @media (max-width: 991.98px) {
        .note-form-layout .note-form-right {
            height: auto;
        }
    }
</style>
@endpush

@section('content')

<div class="container-fluid note-form-page">

    <form action="{{ $isEdit ? route('notes.update', $note) : route('notes.store') }}"
        method="POST" enctype="multipart/form-data">
        @csrf
        @if($isEdit)
        @method('PUT')
        @endif

        {{-- HEADER --}}
        <div class="d-flex align-items-center gap-3 mb-3">
            <a href="{{ $isEdit ? route('notes.index', $note) : route('notes.index') }}"
                class="btn btn-secondary-custom">
                <i class="bi bi-arrow-left"></i>
            </a>
            <h4 class="mb-0">{{ $isEdit ? 'Edit Note' : 'New Note' }}</h4>
        </div>

        <div class="table-card note-form-layout">

            <div class="row g-4">

                {{-- LEFT: TITLE + CONTENT --}}
                <div class="col-lg-6">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Title</label>
                        <input type="text" name="title" class="form-control"
                            placeholder="e.g. Chapter 3 - Greetings"
                            value="{{ old('title', $note->title) }}" required>
                    </div>

                    <div class="mb-0">
                        <label class="form-label fw-semibold">Content</label>
                        <textarea name="content" class="form-control tinymce-editor" rows="14"
                            placeholder="Write your note here...">{{ old('content', $note->content) }}</textarea>
                    </div>

                </div>

                {{-- RIGHT: LINKS + PHOTOS + OPTIONS + ACTIONS --}}
                <div class="col-lg-6">

                    <div class="note-form-right">

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Photos</label>

                            {{-- existing photos (edit only) --}}
                            @if($isEdit && $note->noteFiles->count() > 0)
                            <div class="photo-preview-grid" id="existingPhotoGrid">
                                @foreach($note->noteFiles as $file)
                                <div class="photo-preview-thumb" data-file-id="{{ $file->id }}">
                                    @if($file->mediaAsset)
                                    @php $url = Storage::disk($file->mediaAsset->storage_disk)->url($file->mediaAsset->file_path); @endphp
                                    <img src="{{ $url }}" alt="{{ $file->original_name }}"
                                        data-full="{{ $url }}" data-name="{{ $file->original_name }}">
                                    @else
                                    <div class="photo-preview-missing">
                                        <i class="bi bi-file-earmark-image"></i>
                                    </div>
                                    @endif
                                    <button type="button" class="photo-preview-remove existing-photo-remove"
                                        data-file-id="{{ $file->id }}" title="Remove">&times;</button>
                                </div>
                                @endforeach
                            </div>
                            @endif

                            {{-- newly picked photos --}}
                            <div id="photoPreviewGrid" class="photo-preview-grid"></div>
                        </div>

                        <div class="mb-3">
                            <input type="file" id="photoInput" class="form-control"
                                accept="image/*" multiple>
                            <div class="form-text">
                                Max. 10 MB per photo. You can select multiple, click a photo to view it larger, or use the × to remove it.
                            </div>
                        </div>

                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input" id="pinnedCheck" name="pinned" value="1"
                                {{ old('pinned', $note->pinned) ? 'checked' : '' }}>
                            <label class="form-check-label" for="pinnedCheck">
                                <i class="bi bi-pin-angle-fill"></i>
                                Pin this note for quick access
                            </label>
                        </div>

                        <div class="note-form-actions d-flex justify-content-end gap-2">
                            <button type="submit" class="btn btn-theme">
                                <i class="bi bi-check-circle"></i>
                                {{ $isEdit ? 'Update Note' : 'Save Note' }}
                            </button>
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </form>

</div>

{{-- LIGHTBOX (shared by existing photos + new previews) --}}
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
    const isMobile = window.matchMedia('(max-width: 767.98px)').matches;

    tinymce.init({
        selector: '.tinymce-editor',
        height: isMobile ? 420 : 460,
        menubar: false,
        statusbar: !isMobile,
        plugins: 'lists link',
        toolbar: 'bold italic underline | bullist numlist | link',
        // 16px stops iOS from zooming in when you tap the editor
        content_style: 'body { font-size: 16px; line-height: 1.6; margin: 10px; }',
    });
}



    /* ===================== LIGHTBOX (shared) ===================== */

    const lightbox = document.getElementById('photoLightbox');
    const lightboxImg = document.getElementById('photoLightboxImg');
    const lightboxClose = document.getElementById('photoLightboxClose');
    const lightboxPrev = document.getElementById('photoLightboxPrev');
    const lightboxNext = document.getElementById('photoLightboxNext');
    const lightboxCounter = document.getElementById('photoLightboxCounter');

    let activeGallery = [];
    let currentLightboxIndex = 0;

    function openLightbox(gallery, index) {
        activeGallery = gallery;
        currentLightboxIndex = index;
        showLightboxImage();
        lightbox.classList.remove('d-none');
    }

    function showLightboxImage() {
        const item = activeGallery[currentLightboxIndex];
        if (!item) return;

        lightboxImg.src = item.src;
        lightboxImg.alt = item.name;
        lightboxCounter.textContent = `${currentLightboxIndex + 1} / ${activeGallery.length}`;
        lightboxPrev.disabled = currentLightboxIndex === 0;
        lightboxNext.disabled = currentLightboxIndex === activeGallery.length - 1;
    }

    function showPrev() {
        if (currentLightboxIndex > 0) {
            currentLightboxIndex--;
            showLightboxImage();
        }
    }

    function showNext() {
        if (currentLightboxIndex < activeGallery.length - 1) {
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

    /* ===================== SHARED FORM REFERENCE ===================== */

    const photoInput = document.getElementById('photoInput');
    const form = photoInput.closest('form');

    /* ===================== EXISTING PHOTOS (edit only) ===================== */

    const existingPhotoGrid = document.getElementById('existingPhotoGrid');

    if (existingPhotoGrid) {

        let existingGallery = Array.from(existingPhotoGrid.querySelectorAll('.photo-preview-thumb'))
            .map(function (thumb) {
                const img = thumb.querySelector('img');
                return img
                    ? { fileId: thumb.dataset.fileId, src: img.dataset.full, name: img.dataset.name }
                    : null;
            })
            .filter(Boolean);

        existingPhotoGrid.querySelectorAll('.photo-preview-thumb').forEach(function (thumb) {
            const img = thumb.querySelector('img');
            const removeBtn = thumb.querySelector('.existing-photo-remove');

            if (img) {
                img.addEventListener('click', function () {
                    const index = existingGallery.findIndex(function (item) {
                        return item.fileId === thumb.dataset.fileId;
                    });
                    if (index !== -1) openLightbox(existingGallery, index);
                });
            }

            if (removeBtn) {
                removeBtn.addEventListener('click', function (evt) {
                    evt.stopPropagation();

                    const fileId = removeBtn.dataset.fileId;

                    const hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = 'remove_file_ids[]';
                    hidden.value = fileId;
                    form.appendChild(hidden);

                    existingGallery = existingGallery.filter(function (item) {
                        return item.fileId !== fileId;
                    });

                    thumb.remove();
                });
            }
        });
    }

    /* ===================== NEW PHOTOS: accumulate + preview + lightbox ===================== */

    const previewGrid = document.getElementById('photoPreviewGrid');

    let selectedFiles = [];
    let previewUrls = [];

    const hiddenPhotoInput = document.createElement('input');
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
            <button type="button" class="photo-preview-remove" title="Remove">&times;</button>
        `;

        thumb.querySelector('img').addEventListener('click', function () {
            const gallery = selectedFiles.map(function (f, i) {
                return { src: previewUrls[i], name: f.name };
            });
            openLightbox(gallery, index);
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