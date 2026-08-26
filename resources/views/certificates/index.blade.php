@extends('layouts.app')

@section('title', 'My Certificates')

@section('page-title', 'My Certificates')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/course.css') }}">
<link rel="stylesheet" href="{{ asset('css/qbank.css') }}">
@endpush

@section('content')

<div class="courses-header mb-4">
    <div>
        <h3 class="mb-1">My Certificates</h3>
        <p class="text-muted mb-0">{{ $certifications->count() }} {{ Str::plural('certificate', $certifications->count()) }}.</p>
    </div>
</div>

@if($certifications->isNotEmpty())

<div class="row g-4">

    @foreach($certifications as $cert)

    @php
    $statusBadge = match($cert->status) {
        'verified' => ['bg-success', 'Verified'],
        'pending' => ['bg-warning text-dark', 'Pending Verification'],
        'rejected' => ['bg-danger', 'Rejected'],
    };
    $isExpired = $cert->expired_at && $cert->expired_at->isPast();
    @endphp

    <div class="col-lg-4 col-md-6">

        <a href="{{ route('certificates.show', $cert) }}" class="text-decoration-none text-dark">

            <div class="material-display-card h-100">

                <div class="material-card-header">
                    <div class="material-display-icon">
                        <i class="bi bi-award-fill"></i>
                    </div>
                </div>

                <h5>{{ $cert->certificationType->name }}</h5>

                <span class="material-type">
                    {{ $cert->certificate_num ?: 'No certificate number' }}
                </span>

                <div class="d-flex flex-wrap gap-2 mt-2">

                    <span class="badge {{ $statusBadge[0] }}">{{ $statusBadge[1] }}</span>

                    @if($isExpired)
                    <span class="badge bg-secondary">Expired</span>
                    @endif

                    @if(!is_null($cert->score))
                    <span class="badge bg-light text-dark">Score: {{ rtrim(rtrim(number_format($cert->score, 2), '0'), '.') }}</span>
                    @endif

                </div>

                <p class="mt-3 mb-0 text-muted small">
                    Issued {{ $cert->created_at->format('d M Y') }}
                    @if($cert->expired_at)
                    · Expires {{ $cert->expired_at->format('d M Y') }}
                    @endif
                </p>

            </div>

        </a>

    </div>

    @endforeach

</div>

@else

<div class="empty-state">
    <i class="bi bi-award"></i>
    <h4>No Certificates Yet</h4>
    <p>Certificates you earn will appear here.</p>
</div>

@endif

@endsection