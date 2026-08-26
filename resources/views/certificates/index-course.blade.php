@extends('layouts.app')

@section('title', 'Certificates - ' . $course->name)

@section('page-title', 'Student Certificates')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/course.css') }}">
<link rel="stylesheet" href="{{ asset('css/qbank.css') }}">
@endpush

@section('content')

<div class="mb-4">
    <a href="{{ route('courses.show', $course->id) }}" class="btn btn-secondary-custom">
        <i class="bi bi-arrow-left"></i>
    </a>
</div>

<div class="question-bank-banner mb-4">
    <div>
        <span class="badge bg-light text-dark mb-3">Certificates</span>
        <h2>{{ $course->name }}</h2>
        <p class="mb-0">Certificates held by students enrolled in this course.</p>
    </div>
</div>

<div class="table-card">

    @if($certifications->isNotEmpty())

    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Certification</th>
                    <th>Status</th>
                    <th>Score</th>
                    <th>Issued</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($certifications as $studentId => $certs)
                @foreach($certs as $cert)
                @php
                $statusBadge = match($cert->status) {
                    'verified' => 'bg-success',
                    'pending' => 'bg-warning text-dark',
                    'rejected' => 'bg-danger',
                };
                @endphp
                <tr>
                    <td>{{ $cert->user->name }}</td>
                    <td>{{ $cert->certificationType->name }}</td>
                    <td><span class="badge {{ $statusBadge }}">{{ ucfirst($cert->status) }}</span></td>
                    <td>{{ !is_null($cert->score) ? rtrim(rtrim(number_format($cert->score, 2), '0'), '.') : '—' }}</td>
                    <td>{{ $cert->created_at->format('d M Y') }}</td>
                    <td class="text-end">
                        <a href="{{ route('certificates.show', $cert) }}" class="btn btn-secondary-custom btn-sm">
                            <i class="bi bi-eye"></i> View
                        </a>
                    </td>
                </tr>
                @endforeach
                @endforeach
            </tbody>
        </table>
    </div>

    @else

    <div class="empty-state">
        <i class="bi bi-award"></i>
        <h5>No Certificates Found</h5>
        <p class="text-muted">No students in this course have certificates yet.</p>
    </div>

    @endif

</div>

@endsection