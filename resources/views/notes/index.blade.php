@extends('layouts.app')

@section('title', 'My Notes')

@section('page-title', 'My Notes')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/course.css') }}">
<link rel="stylesheet" href="{{ asset('css/notes.css') }}">
@endpush

@section('content')

<div class="container-fluid">

    {{-- BREADCRUMB + ACTIONS --}}
    <div class="courses-header mb-4">

        <nav class="notes-breadcrumb">

            <a href="{{ route('notes.index') }}" class="{{ !$currentFolder ? 'active' : '' }}">
                <i class="bi bi-house-door-fill"></i>
                My Notes
            </a>

            @foreach($breadcrumbs as $crumb)
            <span class="notes-breadcrumb-sep">/</span>
            <a href="{{ route('notes.index', ['folder' => $crumb->id]) }}"
                class="{{ $currentFolder && $currentFolder->id === $crumb->id ? 'active' : '' }}">
                {{ $crumb->name }}
            </a>
            @endforeach

        </nav>

        <div class="d-flex gap-2">

            <button type="button"
                class="btn btn-secondary-custom"
                data-bs-toggle="modal"
                data-bs-target="#createFolderModal">

                <i class="bi bi-folder-plus"></i>
                New Folder

            </button>

            <a href="{{ route('notes.create', $currentFolder ? ['folder' => $currentFolder->id] : []) }}" class="btn btn-theme">
                <i class="bi bi-file-earmark-plus"></i>
                New Note
            </a>

        </div>

    </div>

    {{-- FOLDERS --}}
    @if($subfolders->count() > 0)
    <h6 class="notes-section-label">Folders</h6>

    <div class="folder-grid mb-4">

        @foreach($subfolders as $folder)

        <div class="folder-card"
            onclick="if (!event.target.closest('.folder-actions')) { window.location = '{{ route('notes.index', ['folder' => $folder->id]) }}'; }">

            <div class="folder-card-icon">
                <i class="bi bi-folder-fill"></i>
            </div>

            <div class="folder-card-name">
                {{ $folder->name }}
            </div>

            <div class="folder-actions">

                <button type="button"
                    class="icon-action-btn edit-btn"
                    data-bs-toggle="modal"
                    data-bs-target="#editFolderModal{{ $folder->id }}">
                    <i class="bi bi-pencil-fill"></i>
                </button>

                <form action="{{ route('folders.destroy', $folder) }}"
                    method="POST"
                    onsubmit="return confirm('Delete this folder? Notes inside will be moved to its parent.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="icon-action-btn delete-btn">
                        <i class="bi bi-trash-fill"></i>
                    </button>
                </form>

            </div>

        </div>

        {{-- RENAME FOLDER MODAL --}}
        <div class="modal fade" id="editFolderModal{{ $folder->id }}" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content custom-modal">

                    <div class="modal-header border-0">
                        <h4 class="modal-title">Rename Folder</h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <form action="{{ route('folders.update', $folder) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="modal-body">
                            <label class="form-label">Folder Name</label>
                            <input type="text" name="name" class="form-control" value="{{ $folder->name }}" required>
                        </div>

                        <div class="modal-footer border-0">
                            <button type="button" class="btn btn-secondary-custom" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-theme">
                                <i class="bi bi-check-circle"></i>
                                Save
                            </button>
                        </div>

                    </form>

                </div>
            </div>
        </div>

        @endforeach

    </div>
    @endif

    {{-- PINNED NOTES --}}
    @php $pinnedNotes = $notes->where('pinned', true); @endphp

    @if($pinnedNotes->count() > 0)
    <h6 class="notes-section-label">
        <i class="bi bi-pin-angle-fill"></i>
        Pinned
    </h6>

    <div class="note-grid mb-4">
        @foreach($pinnedNotes as $note)
        @include('notes.partials.note-card', ['note' => $note])
        @endforeach
    </div>
    @endif

    {{-- ALL NOTES --}}
    @php $unpinnedNotes = $notes->where('pinned', false); @endphp

    <h6 class="notes-section-label">Notes</h6>

    @if($unpinnedNotes->count() > 0)
    <div class="note-grid">
        @foreach($unpinnedNotes as $note)
        @include('notes.partials.note-card', ['note' => $note])
        @endforeach
    </div>
    @elseif($subfolders->count() === 0 && $pinnedNotes->count() === 0)
    <div class="empty-state">
        <i class="bi bi-journal-text"></i>
        <h4>No Notes Yet</h4>
        <p>Create your first note to start organizing your learning.</p>
    </div>
    @endif

</div>

{{-- CREATE FOLDER MODAL --}}
<div class="modal fade" id="createFolderModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-modal">

            <div class="modal-header border-0">
                <h4 class="modal-title">New Folder</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form action="{{ route('folders.store') }}" method="POST">
                @csrf

                <input type="hidden" name="parent_folder_id" value="{{ $currentFolder->id ?? '' }}">

                <div class="modal-body">
                    <label class="form-label">Folder Name</label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Chapter 1 Vocabulary" required>
                </div>

                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary-custom" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-theme">
                        <i class="bi bi-check-circle"></i>
                        Create Folder
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>

@include('notes.partials.note-modal')

@endsection

@push('scripts')
<script>
    document.addEventListener('note-saved', function() {
        window.location.reload();
    });
</script>
@endpush