@php
    $photoFiles = $note->files->filter(fn($f) => $f->mediaAsset);
    $photoPreview = $photoFiles->take(3);
    $extraPhotoCount = max(0, $photoFiles->count() - $photoPreview->count());
@endphp

<div class="note-card"
    onclick="if (!event.target.closest('.note-card-actions')) { window.location = '{{ route('notes.show', $note) }}'; }">

    <div class="note-card-top">

        <h5 class="note-card-title">
            {{ $note->title }}
        </h5>

        <div class="note-card-actions">

            <form action="{{ route('notes.pin', $note) }}" method="POST">
                @csrf
                @method('PATCH')
                <button type="submit" class="icon-action-btn pin-btn {{ $note->pinned ? 'pinned' : '' }}"
                    title="{{ $note->pinned ? 'Unpin' : 'Pin' }}">
                    <i class="bi {{ $note->pinned ? 'bi-pin-angle-fill' : 'bi-pin-angle' }}"></i>
                </button>
            </form>

            <a href="{{ route('notes.edit', $note) }}" class="icon-action-btn edit-btn">
                <i class="bi bi-pencil-fill"></i>
            </a>

            <form action="{{ route('notes.destroy', $note) }}" method="POST"
                onsubmit="return confirm('Delete this note?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="icon-action-btn delete-btn">
                    <i class="bi bi-trash-fill"></i>
                </button>
            </form>

        </div>

    </div>

    <p class="note-card-preview">
        {{ Str::limit(strip_tags($note->content), 140) }}
    </p>

    @if($photoPreview->count() > 0)
    <div class="note-card-photos">

        @foreach($photoPreview as $file)
        <div class="note-card-photo">
            <img src="{{ Storage::disk($file->mediaAsset->storage_disk)->url($file->mediaAsset->file_path) }}"
                alt="{{ $file->original_name }}">
        </div>
        @endforeach

        @if($extraPhotoCount > 0)
        <div class="note-card-photo note-card-photo-more">
            +{{ $extraPhotoCount }}
        </div>
        @endif

    </div>
    @endif

    <div class="note-card-footer">

        @if($note->materials->count() > 0)
        <div class="note-card-materials">
            @foreach($note->materials->take(2) as $material)
            <span class="badge bg-light text-dark">
                <i class="bi bi-link-45deg"></i> {{ $material->name }}
            </span>
            @endforeach
            @if($note->materials->count() > 2)
            <span class="badge bg-light text-dark">+{{ $note->materials->count() - 2 }}</span>
            @endif
        </div>
        @endif

        <span class="note-card-date">
            {{ $note->updated_at->diffForHumans() }}
        </span>

    </div>

</div>