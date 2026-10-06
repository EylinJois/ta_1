@php
    $photoFiles = $note->noteFiles->filter(fn($f) => $f->mediaAsset);
    $photoPreview = $photoFiles->take(3);
    $extraPhotoCount = max(0, $photoFiles->count() - $photoPreview->count());
@endphp

<div class="note-card"
    onclick="if (!event.target.closest('.note-card-actions')) { window.location = '{{ route('notes.edit', $note) }}'; }">

    <div class="note-card-top">

        <h5 class="note-card-title">
            {{ $note->title }}
        </h5>

        <div class="note-card-actions">

            <button type="button"
                class="icon-action-btn pin-btn {{ $note->pinned ? 'pinned' : '' }}"
                data-note-id="{{ $note->id }}"
                data-pinned="{{ $note->pinned ? '1' : '0' }}"
                title="{{ $note->pinned ? 'Unpin' : 'Pin' }}">
                <i class="bi {{ $note->pinned ? 'bi-pin-angle-fill' : 'bi-pin-angle' }}"></i>
            </button>

            <form action="{{ route('notes.destroy', $note->id) }}" method="POST"
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

        <span class="note-card-date">
            {{ $note->updated_at->diffForHumans() }}
        </span>

    </div>

</div>