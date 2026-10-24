@extends('layouts.app')

@section('title', 'My Notes')

@section('page-title', 'My Notes')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/course.css') }}">
<link rel="stylesheet" href="{{ asset('css/notes.css') }}">
@endpush

@section('content')

<div class="container-fluid">

    {{-- HEADER + ACTIONS --}}
    <div class="mb-4 row justify-content-between">

        <nav class="notes-breadcrumb col">

            <a href="{{ route('notes.index') }}" class="h2 text-decoration-none text-dark">
                My Notes
            </a>

        </nav>

        <div class="col justify-content-end d-flex">
            <div class="">
                <a href="{{ route('notes.create') }}" class="btn btn-theme btn-sm">
    <i class="bi bi-file-earmark-plus"></i>
    New Note
</a>
            </div>
        </div>

    </div>

    <div class="note-grid">
        @forelse($notes as $note)
        @include('notes.partials.note-card', ['note' => $note])
        @empty
        <div style="grid-column: 1 / -1;">
            <div class="alert text-center">
                <i class="bi bi-info-circle"></i>
                You have no notes yet. Click "New Note" to create one.
            </div>
        </div>
        @endforelse
    </div>

</div>


@endsection

@push('scripts')
<script>
    document.addEventListener('note-saved', function() {
        window.location.reload();
    });

    const pinUrlTemplate = "{{ route('notes.pin', ['note' => '__ID__']) }}";

    document.querySelectorAll('.pin-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();

            const url = pinUrlTemplate.replace('__ID__', btn.dataset.noteId);

            fetch(url, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(function(res) {
                if (!res.ok) throw new Error('Request failed: ' + res.status);
                window.location.reload();
            })
            .catch(function(err) {
                console.error(err);
                alert('Could not update pin. Please try again.');
            });
        });
    });
</script>
@endpush