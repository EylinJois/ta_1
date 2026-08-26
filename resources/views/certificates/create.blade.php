@extends('layouts.app')

@section('title', 'Issue Certificate')

@section('page-title', 'Issue Certificate')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/course.css') }}">
@endpush

@section('content')

<div class="form-card">

    <div class="form-card-header">
        <div class="d-flex align-items-center gap-3">
            <div class="header-icon">
                <i class="bi bi-award-fill"></i>
            </div>
            <div>
                <h3 class="mb-1">Issue Certificate</h3>
                <p class="text-muted mb-0">Award a certificate using the standard template.</p>
            </div>
        </div>
    </div>

    <form action="{{ route('certificates.store') }}" method="POST">
        @csrf

        <div class="mb-4">
            <label class="form-label">Student</label>
            <select name="user_id" class="form-select" required>
                <option value="" disabled selected>Choose a student...</option>
                @foreach($students as $student)
                <option value="{{ $student->id }}">{{ $student->name }} ({{ $student->email }})</option>
                @endforeach
            </select>
        </div>

        <div class="mb-4">
            <label class="form-label">Certification Type</label>
            <select name="certification_type_id" class="form-select" required>
                <option value="" disabled selected>Choose a type...</option>
                @foreach($certificationTypes as $type)
                <option value="{{ $type->id }}">{{ $type->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="row">
            <div class="col-md-6 mb-4">
                <label class="form-label">Certificate Number <small class="text-muted">(optional)</small></label>
                <input type="text" name="certificate_num" class="form-control">
            </div>
            <div class="col-md-6 mb-4">
                <label class="form-label">Score <small class="text-muted">(optional)</small></label>
                <input type="number" step="0.01" min="0" name="score" class="form-control">
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label">Expires On <small class="text-muted">(optional)</small></label>
            <input type="date" name="expired_at" class="form-control">
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-theme">
                <i class="bi bi-check-circle me-1"></i>
                Issue Certificate
            </button>
        </div>

    </form>

</div>

@endsection