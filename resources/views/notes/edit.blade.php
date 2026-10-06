@extends('layouts.app')

@section('title', 'Edit Note')

@section('page-title', 'Edit Note')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/notes.css') }}">
@endpush

@section('content')

<div class="container-fluid">

    <div class="table-card">

        <form action="{{ route('notes.update', $note) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="courses-header mb-4">
                <a href="{{ route('notes.index', $folder ? ['folder' => $folder->id] : []) }}" class="btn btn-secondary-custom">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h4 class="mb-0">Edit Note</h4>
            </div>

            <div class="row">

                {{-- MAIN --}}
                <div class="col-lg-8">

                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control"
                            value="{{ old('title', $note->title) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Content</label>
                        <textarea name="content" class="form-control tinymce-editor" rows="10">{{ old('content', $note->content) }}</textarea>
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
                                <option value="{{ $f->id }}" {{ old('folder_id', $note->folder_id) == $f->id ? 'selected' : '' }}>
                                    {{ $f->breadcrumb ?? $f->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-check mb-4">
                            <input type="checkbox" class="form-check-input" id="pinnedCheck" name="pinned" value="1"
                                {{ old('pinned', $note->pinned) ? 'checked' : '' }}>
                            <label class="form-check-label" for="pinnedCheck">
                                <i class="bi bi-pin-angle-fill"></i>
                                Pin this note for quick access
                            </label>
                        </div>

                    </div>
@if($note->noteFiles->count() > 0)
                    <div class="mb-3">
                        <label class="form-label">Current Photos</label>
                        <div class="photo-preview-grid" id="existingPhotoGrid">
                            @foreach($note->noteFiles as $index => $file)
                            <div class="photo-preview-thumb" data-file-id="{{ $file->id }}" data-index="{{ $index }}">
                                @if($file->mediaAsset)
                                <img src="{{ Storage::disk($file->mediaAsset->storage_disk)->url($file->mediaAsset->file_path) }}"
                                    alt="{{ $file->original_name }}"
                                    data-full="{{ Storage::disk($file->mediaAsset->storage_disk)->url($file->mediaAsset->file_path) }}"
                                    data-name="{{ $file->original_name }}">
                                @else
                                <div class="photo-preview-missing">
                                    <i class="bi bi-file-earmark-image"></i>
                                </div>
                                @endif
                                <button type="button" class="photo-preview-remove existing-photo-remove" data-file-id="{{ $file->id }}" title="Remove">
                                    &times;
                                </button>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label">
                            Add More Photos
                        </label>
                        <input type="file" id="photoInput" class="form-control"
                            accept="image/*" multiple>
                        <div class="form-text">
                            You can add photos across multiple selections. Click a photo to view it larger, or use the × to remove it.
                        </div>
                        <div id="photoPreviewGrid" class="photo-preview-grid mt-3"></div>
                    </div>
                </div>

            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="{{ route('notes.show', $note) }}" class="btn btn-secondary-custom">
                    Cancel
                </a>
                <button type="submit" class="btn btn-theme">
                    <i class="bi bi-check-circle"></i>
                    Update Note
                </button>
            </div>

        </form>

    </div>

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
    document.addEventListener('DOMContentLoaded', function() {

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

        /* ===================== LIGHTBOX (shared) ===================== */

        const lightbox = document.getElementById('photoLightbox');
        const lightboxImg = document.getElementById('photoLightboxImg');
        const lightboxClose = document.getElementById('photoLightboxClose');
        const lightboxPrev = document.getElementById('photoLightboxPrev');
        const lightboxNext = document.getElementById('photoLightboxNext');
        const lightboxCounter = document.getElementById('photoLightboxCounter');

        // The active gallery is whichever set of {src, name} the lightbox was
        // opened from — existing server photos, or the newly-picked previews.
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
        lightbox.addEventListener('click', function(e) {
            if (e.target === lightbox) closeLightbox();
        });
        document.addEventListener('keydown', function(e) {
            if (lightbox.classList.contains('d-none')) return;
            if (e.key === 'Escape') closeLightbox();
            if (e.key === 'ArrowLeft') showPrev();
            if (e.key === 'ArrowRight') showNext();
        });

        /* ===================== SHARED FORM REFERENCE ===================== */

const photoInput = document.getElementById('photoInput');
const form = photoInput.closest('form');


        /* ===================== EXISTING PHOTOS: click-to-view + removal ===================== */

        const existingPhotoGrid = document.getElementById('existingPhotoGrid');

        // Mutable so removed photos actually drop out of the lightbox's
        // navigable set too, not just the visible grid.
        let existingGallery = existingPhotoGrid ?
            Array.from(existingPhotoGrid.querySelectorAll('.photo-preview-thumb')).map(function(thumb) {
                const img = thumb.querySelector('img');
                return img ?
                    {
                        fileId: thumb.dataset.fileId,
                        src: img.dataset.full,
                        name: img.dataset.name
                    } :
                    null;
            }).filter(Boolean) :
            [];

        function bindExistingThumb(thumb) {
            const img = thumb.querySelector('img');
            const removeBtn = thumb.querySelector('.existing-photo-remove');

            if (img) {
                img.addEventListener('click', function() {
                    const fileId = thumb.dataset.fileId;
                    const index = existingGallery.findIndex(function(item) {
                        return item.fileId === fileId;
                    });
                    if (index !== -1) {
                        openLightbox(existingGallery, index);
                    }
                });
            }

            if (removeBtn) {
                removeBtn.addEventListener('click', function(evt) {
                    evt.stopPropagation();

                    const fileId = removeBtn.dataset.fileId;

                    // Record the removal so it's actually submitted with the form.
                    const hiddenRemoveInput = document.createElement('input');
                    hiddenRemoveInput.type = 'hidden';
                    hiddenRemoveInput.name = 'remove_file_ids[]';
                    hiddenRemoveInput.value = fileId;
                    form.appendChild(hiddenRemoveInput);

                    // Drop it from the lightbox's navigable set too.
                    existingGallery = existingGallery.filter(function(item) {
                        return item.fileId !== fileId;
                    });

                    // Disappear from the UI immediately, same as new-photo removal.
                    thumb.remove();
                });
            }
        }

        if (existingPhotoGrid) {
            existingPhotoGrid.querySelectorAll('.photo-preview-thumb').forEach(bindExistingThumb);
        }

        /* ===================== NEW PHOTOS: accumulate + preview + lightbox ===================== */

        const previewGrid = document.getElementById('photoPreviewGrid');

        let selectedFiles = [];
        let previewUrls = [];

        let hiddenPhotoInput = document.createElement('input');
        hiddenPhotoInput.type = 'file';
        hiddenPhotoInput.name = 'photos[]';
        hiddenPhotoInput.multiple = true;
        hiddenPhotoInput.style.display = 'none';
        form.appendChild(hiddenPhotoInput);

        function syncHiddenInput() {
            const dataTransfer = new DataTransfer();
            selectedFiles.forEach(function(file) {
                dataTransfer.items.add(file);
            });
            hiddenPhotoInput.files = dataTransfer.files;
        }

        function renderPreviews() {
            previewGrid.innerHTML = '';

            selectedFiles.forEach(function(file, index) {
                if (!previewUrls[index]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
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

            thumb.querySelector('img').addEventListener('click', function() {
                const newGallery = selectedFiles.map(function(f, i) {
                    return {
                        src: previewUrls[i],
                        name: f.name
                    };
                });
                openLightbox(newGallery, index);
            });

            thumb.querySelector('.photo-preview-remove').addEventListener('click', function(evt) {
                evt.stopPropagation();
                selectedFiles.splice(index, 1);
                previewUrls.splice(index, 1);
                syncHiddenInput();
                renderPreviews();
            });

            previewGrid.appendChild(thumb);
        }

        photoInput.addEventListener('change', function() {
            selectedFiles = selectedFiles.concat(Array.from(photoInput.files));
            photoInput.value = '';

            syncHiddenInput();
            renderPreviews();
        });

    });
</script>
@endpush
@endsection