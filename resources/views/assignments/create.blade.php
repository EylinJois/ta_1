@extends('layouts.app')

@section('title', 'Create Assignment')

@section('page-title', 'New Assignment')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/course.css') }}">
<link rel="stylesheet" href="{{ asset('css/qbank.css') }}">
@endpush

@section('content')

<div class="form-card">

    <div class="form-card-header">

        <div class="d-flex align-items-center gap-3">
            <div class="header-icon">
                <i class="bi bi-file-earmark-text-fill"></i>
            </div>
            <div>
                <h3 class="mb-1">Create Assignment</h3>
                <p class="text-muted mb-0">In {{ $section->name }}</p>
            </div>
        </div>

    </div>

    <form action="{{ route('assignments.store', $section) }}" method="POST">
        @csrf

        @include('assignments._form')

        <div class="form-actions">
            <a href="{{ route('sections.show', $section) }}" class="btn btn-secondary-custom">
                Cancel
            </a>
            <button type="submit" class="btn btn-theme">
                <i class="bi bi-check-circle me-1"></i>
                Create Assignment
            </button>
        </div>

    </form>

</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    const submissionType = document.getElementById('submissionType');
    const fileConfigSection = document.getElementById('fileConfigSection');
    const mimeCheckboxes = document.querySelectorAll('.mime-checkbox');
    const mimeCustom = document.getElementById('mimeCustom');
    const acceptedMimeJson = document.getElementById('acceptedMimeJson');

    function refreshFileSection() {
        const show = submissionType.value === 'file' || submissionType.value === 'both';
        fileConfigSection.classList.toggle('d-none', !show);
        fileConfigSection.querySelectorAll('input').forEach(function (field) {
            field.disabled = !show;
        });
    }

    function syncMimeJson() {
        const checked = Array.from(mimeCheckboxes).filter(cb => cb.checked).map(cb => cb.value);
        const custom = mimeCustom.value.split(',').map(s => s.trim().toLowerCase().replace(/^\./, '')).filter(Boolean);
        acceptedMimeJson.value = JSON.stringify([...new Set([...checked, ...custom])]);
    }

    submissionType.addEventListener('change', refreshFileSection);
    mimeCheckboxes.forEach(cb => cb.addEventListener('change', syncMimeJson));
    mimeCustom.addEventListener('input', syncMimeJson);

    refreshFileSection();
    syncMimeJson();

});
</script>
@endpush