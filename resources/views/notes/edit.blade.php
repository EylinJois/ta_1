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

                <a href="{{ route('notes.show', $note) }}" class="btn btn-secondary-custom">
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
                        <textarea name="content" class="form-control" rows="10">{{ old('content', $note->content) }}</textarea>
                    </div>

                    @if($note->files->count() > 0)
                    <div class="mb-3">

                        <label class="form-label">Current Photos</label>

                        <div class="photo-preview-grid">

                            @foreach($note->files as $file)
                            <div class="photo-preview-thumb" data-file-id="{{ $file->id }}">

                                @if($file->mediaAsset)
                                <img src="{{ Storage::disk($file->mediaAsset->storage_disk)->url($file->mediaAsset->file_path) }}"
                                    alt="{{ $file->original_name }}">
                                @else
                                <div class="photo-preview-missing">
                                    <i class="bi bi-file-earmark-image"></i>
                                </div>
                                @endif

                                <label class="photo-remove-toggle">
                                    <input type="checkbox" name="remove_file_ids[]" value="{{ $file->id }}">
                                    <span><i class="bi bi-trash-fill"></i> Remove</span>
                                </label>

                            </div>
                            @endforeach

                        </div>

                    </div>
                    @endif

                    <div class="mb-3">

                        <label class="form-label">
                            Add More Photos
                        </label>

                        <input type="file" name="photos[]" id="photoInput" class="form-control"
                            accept="image/*" multiple>

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

                        <div class="mb-2">
                            <label class="form-label">
                                Link to Materials
                                <span class="text-muted small">(optional)</span>
                            </label>
                        </div>

                        @php $linkedMaterialIds = $note->materials->pluck('id')->toArray(); @endphp

                        <div class="note-material-picker">
                            @forelse($materials as $material)
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input"
                                    name="material_ids[]" value="{{ $material->id }}"
                                    id="material{{ $material->id }}"
                                    {{ in_array($material->id, old('material_ids', $linkedMaterialIds)) ? 'checked' : '' }}>
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

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {

        const photoInput = document.getElementById('photoInput');
        const previewGrid = document.getElementById('photoPreviewGrid');

        photoInput.addEventListener('change', function() {

            previewGrid.innerHTML = '';

            Array.from(photoInput.files).forEach(function(file) {

                const reader = new FileReader();

                reader.onload = function(e) {
                    const thumb = document.createElement('div');
                    thumb.className = 'photo-preview-thumb';
                    thumb.innerHTML = `<img src="${e.target.result}" alt="${file.name}">`;
                    previewGrid.appendChild(thumb);
                };

                reader.readAsDataURL(file);

            });

        });

        // Visually grey out photos marked for removal.
        document.querySelectorAll('.photo-remove-toggle input[type="checkbox"]').forEach(function(cb) {
            cb.addEventListener('change', function() {
                cb.closest('.photo-preview-thumb').classList.toggle('marked-for-removal', cb.checked);
            });
        });

    });
</script>
@endpush

@endsection