{{--
    "Notes on this material" panel.

    Include on materials/show.blade.php with:
        @include('notes.partials.material-notes-panel', ['material' => $material])

    Self-contained styling (scoped class names) so it doesn't depend on
    notes.css being loaded on the materials.show page.
--}}

@php
    $materialNotes = auth()->user()
        ->notes()
        ->whereHas('materials', fn($q) => $q->where('materials.id', $material->id))
        ->with(['files.mediaAsset'])
        ->latest('updated_at')
        ->get();
@endphp

<style>
    .material-notes-panel {
        background: #fff;
        border-radius: 20px;
        padding: 24px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.05);
        margin-top: 24px;
    }

    .material-notes-panel-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        margin-bottom: 16px;
    }

    .material-notes-panel-header h5 {
        margin: 0;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .material-notes-panel-header-actions {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .material-notes-minimize-btn {
        width: 32px;
        height: 32px;
        border-radius: 10px;
        border: 1px solid #eee;
        background: #fff;
        color: #999;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: 0.2s;
        flex-shrink: 0;
    }

    .material-notes-minimize-btn:hover {
        border-color: #F77F00;
        color: #F77F00;
    }

    .material-notes-body {
        overflow: hidden;
        transition: max-height 0.25s ease, opacity 0.2s ease;
    }

    .material-notes-panel.collapsed .material-notes-body {
        max-height: 0 !important;
        opacity: 0;
        margin: 0;
    }

    .material-notes-panel.collapsed .material-notes-minimize-btn i {
        transform: rotate(180deg);
    }

    .material-notes-minimize-btn i {
        transition: transform 0.2s ease;
    }

    .material-note-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 14px;
        border: 1px solid #eee;
        border-radius: 14px;
        text-decoration: none;
        color: inherit;
        transition: 0.2s;
        margin-bottom: 10px;
    }

    .material-note-item:last-child {
        margin-bottom: 0;
    }

    .material-note-item:hover {
        border-color: #F77F00;
        background: #fff8f0;
        color: inherit;
    }

    .material-note-item-icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: #fff8f0;
        color: #F77F00;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 16px;
    }

    .material-note-item-icon.pinned {
        background: rgba(214, 40, 40, 0.1);
        color: #D62828;
    }

    .material-note-item-body {
        flex: 1;
        min-width: 0;
    }

    .material-note-item-title {
        font-weight: 600;
        margin-bottom: 2px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .material-note-item-preview {
        font-size: 13px;
        color: #888;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .material-note-item-date {
        font-size: 12px;
        color: #bbb;
        flex-shrink: 0;
        white-space: nowrap;
    }

    .material-notes-empty {
        text-align: center;
        padding: 24px 12px;
        color: #999;
    }

    .material-notes-empty i {
        font-size: 28px;
        color: #ddd;
        margin-bottom: 8px;
        display: block;
    }
</style>

<div class="material-notes-panel" id="materialNotesPanel{{ $material->id }}">

    <div class="material-notes-panel-header">

        <h5>
            <i class="bi bi-journal-text"></i>
            Notes on this material
        </h5>

        <div class="material-notes-panel-header-actions">

            <a href="{{ route('notes.create', ['material' => $material->id]) }}" class="btn btn-theme btn-sm">
                <i class="bi bi-plus-circle"></i>
                Take a Note
            </a>

            <button type="button" class="material-notes-minimize-btn" data-panel-toggle title="Minimize">
                <i class="bi bi-chevron-up"></i>
            </button>

        </div>

    </div>

    <div class="material-notes-body">

        @if($materialNotes->count() > 0)

        @foreach($materialNotes as $note)
        <a href="{{ route('notes.show', $note) }}" class="material-note-item">

            <div class="material-note-item-icon {{ $note->pinned ? 'pinned' : '' }}">
                <i class="bi {{ $note->pinned ? 'bi-pin-angle-fill' : 'bi-journal-text' }}"></i>
            </div>

            <div class="material-note-item-body">
                <div class="material-note-item-title">{{ $note->title }}</div>
                <div class="material-note-item-preview">{{ Str::limit(strip_tags($note->content), 80) }}</div>
            </div>

            <div class="material-note-item-date">
                {{ $note->updated_at->diffForHumans(null, true) }}
            </div>

        </a>
        @endforeach

        @else

        <div class="material-notes-empty">
            <i class="bi bi-journal-plus"></i>
            No notes yet for this material — take one to remember what you learned.
        </div>

        @endif

    </div>

</div>

<script>
    (function() {
        const panel = document.getElementById('materialNotesPanel{{ $material->id }}');
        const toggleBtn = panel.querySelector('[data-panel-toggle]');
        const body = panel.querySelector('.material-notes-body');
        const storageKey = 'material-notes-collapsed-{{ $material->id }}';

        function setCollapsed(collapsed) {
            panel.classList.toggle('collapsed', collapsed);
            body.style.maxHeight = collapsed ? '0px' : body.scrollHeight + 'px';
            toggleBtn.title = collapsed ? 'Expand' : 'Minimize';
            localStorage.setItem(storageKey, collapsed ? '1' : '0');
        }

        // Restore prior state (defaults to expanded).
        setCollapsed(localStorage.getItem(storageKey) === '1');

        toggleBtn.addEventListener('click', function() {
            setCollapsed(!panel.classList.contains('collapsed'));
        });

        // Keep max-height accurate if content reflows (e.g. window resize).
        window.addEventListener('resize', function() {
            if (!panel.classList.contains('collapsed')) {
                body.style.maxHeight = body.scrollHeight + 'px';
            }
        });
    })();
</script>