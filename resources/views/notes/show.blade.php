@extends('layouts.app')

@section('title', $note->title)

@section('page-title', 'Note')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/course.css') }}">
<link rel="stylesheet" href="{{ asset('css/notes.css') }}">
@endpush

@section('content')

<div class="container-fluid">

    <div class="table-card note-show-card">

        {{-- HEADER --}}
        <div class="courses-header mb-3">

            <a href="{{ route('notes.index', $note->folder_id ? ['folder' => $note->folder_id] : []) }}"
                class="btn btn-secondary-custom">
                <i class="bi bi-arrow-left"></i>
            </a>

            <div>
                <nav class="notes-breadcrumb notes-breadcrumb-sm">

                    <a href="{{ route('notes.index') }}">
                        <i class="bi bi-house-door-fill"></i> My Notes
                    </a>

                    @foreach($breadcrumbs as $crumb)
                    <span class="notes-breadcrumb-sep">/</span>
                    <a href="{{ route('notes.index', ['folder' => $crumb->id]) }}">
                        {{ $crumb->name }}
                    </a>
                    @endforeach

                </nav>
            </div>

            <div class="d-flex gap-2">

                <form action="{{ route('notes.pin', $note) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-secondary-custom">
                        <i class="bi {{ $note->pinned ? 'bi-pin-angle-fill' : 'bi-pin-angle' }}"></i>
                        {{ $note->pinned ? 'Pinned' : 'Pin' }}
                    </button>
                </form>

                <button type="button" class="btn btn-secondary-custom" onclick="openNoteModal({ mode: 'edit', noteId: {{ $note->id }} })">
                    <i class="bi bi-pencil-fill"></i>
                    Edit
                </button>

                <form action="{{ route('notes.destroy', $note) }}" method="POST"
                    onsubmit="return confirm('Delete this note? This cannot be undone.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash-fill"></i>
                        Delete
                    </button>
                </form>

            </div>

        </div>

        {{-- TITLE + META --}}
        <h2 class="note-show-title">
            @if($note->pinned)
            <i class="bi bi-pin-angle-fill note-show-pin-icon"></i>
            @endif
            {{ $note->title }}
        </h2>

        <div class="note-show-meta">
            <span>
                <i class="bi bi-clock-history"></i>
                Updated {{ $note->updated_at->diffForHumans() }}
            </span>
            @if($note->folder)
            <span>
                <i class="bi bi-folder-fill"></i>
                {{ $note->folder->name }}
            </span>
            @endif
        </div>

        {{-- CONTENT --}}
        @if($note->content)
        <div class="note-show-content">
            {{ $note->content }}
        </div>
        @endif

        {{-- PHOTOS --}}
        @php $photoFiles = $note->files->filter(fn($f) => $f->mediaAsset); @endphp

        @if($photoFiles->count() > 0)
        <div class="note-show-section">

            <h6 class="notes-section-label">
                <i class="bi bi-images"></i>
                Photos
            </h6>

            <div class="note-gallery">
                @foreach($photoFiles as $file)
                <button type="button" class="note-gallery-item"
                    data-bs-toggle="modal"
                    data-bs-target="#photoModal"
                    data-src="{{ Storage::disk($file->mediaAsset->storage_disk)->url($file->mediaAsset->file_path) }}"
                    data-name="{{ $file->original_name }}">
                    <img src="{{ Storage::disk($file->mediaAsset->storage_disk)->url($file->mediaAsset->file_path) }}"
                        alt="{{ $file->original_name }}">
                </button>
                @endforeach
            </div>

        </div>
        @endif

        {{-- LINKED MATERIALS --}}
        @if($note->materials->count() > 0)
        <div class="note-show-section">

            <h6 class="notes-section-label">
                <i class="bi bi-link-45deg"></i>
                Linked Materials
            </h6>

            <div class="note-linked-materials">
                @foreach($note->materials as $material)
                <a href="{{ route('materials.show', $material) }}" class="note-linked-material">
                    <i class="bi bi-journal-text"></i>
                    {{ $material->name }}
                </a>
                @endforeach
            </div>

        </div>
        @endif

    </div>

</div>

{{-- PHOTO LIGHTBOX MODAL --}}
<div class="modal fade" id="photoModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content custom-modal photo-modal-content">

            <div class="modal-header border-0">
                <h6 class="modal-title" id="photoModalName"></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body text-center pt-0">
                <img id="photoModalImage" src="" alt="" class="photo-modal-image">
            </div>

        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {

        const photoModal = document.getElementById('photoModal');

        photoModal.addEventListener('show.bs.modal', function(event) {
            const trigger = event.relatedTarget;
            document.getElementById('photoModalImage').src = trigger.dataset.src;
            document.getElementById('photoModalName').textContent = trigger.dataset.name;
        });

        document.addEventListener('note-saved', function() {
            window.location.reload();
        });

    });
</script>
@endpush

@endsection