@extends('layouts.app')

@section('title', $userCertification->certificationType->name)

@section('page-title', 'Certificate')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/course.css') }}">
@endpush

@section('content')

<div class="mb-4">
    <a href="{{ route('certificates.index') }}" class="btn btn-secondary-custom">
        <i class="bi bi-arrow-left"></i>
    </a>
</div>

@php
$statusBadge = match($userCertification->status) {
    'verified' => ['bg-success', 'Verified'],
    'pending' => ['bg-warning text-dark', 'Pending Verification'],
    'rejected' => ['bg-danger', 'Rejected'],
};
@endphp

{{-- Certificate "template" design --}}
<div class="table-card mb-4 text-center py-5" style="border: 3px solid #D62828; border-radius: 24px;">

    <i class="bi bi-award-fill" style="font-size: 60px; color: #D62828;"></i>

    <h6 class="text-muted text-uppercase mt-3">Certificate of Completion</h6>
    <h2 class="my-3">{{ $userCertification->user->name }}</h2>
    <p class="lead mb-1">has been awarded</p>
    <h4 class="mb-4">{{ $userCertification->certificationType->name }}</h4>

    @if($userCertification->certificate_num)
    <p class="text-muted mb-1">Certificate No. {{ $userCertification->certificate_num }}</p>
    @endif

    @if(!is_null($userCertification->score))
    <p class="text-muted mb-1">Score: {{ rtrim(rtrim(number_format($userCertification->score, 2), '0'), '.') }}</p>
    @endif

    <p class="text-muted">
        Issued {{ $userCertification->created_at->format('d F Y') }}
        @if($userCertification->expired_at)
        · Valid until {{ $userCertification->expired_at->format('d F Y') }}
        @endif
    </p>

    <span class="badge {{ $statusBadge[0] }}">{{ $statusBadge[1] }}</span>

</div>

@if($userCertification->status === 'verified')
<div class="table-card mb-4">
    <p class="mb-0 text-muted small">
        <i class="bi bi-shield-check"></i>
        Verified by {{ $userCertification->verifier->name ?? 'System' }} on {{ $userCertification->verified_at->format('d M Y') }}
    </p>
</div>
@endif

@if($userCertification->files->isNotEmpty())
<div class="table-card">
    <h5 class="mb-3"><i class="bi bi-paperclip"></i> Supporting Files</h5>
    <ul class="list-unstyled mb-0">
        @foreach($userCertification->files as $file)
        <li class="d-flex align-items-center justify-content-between border rounded-3 px-3 py-2 mb-2">
            <span><i class="bi bi-file-earmark-check text-success me-1"></i> {{ $file->mediaAsset->original_name ?? 'File' }}</span>
            @if($file->mediaAsset)
            <a href="{{ route('media.download', $file->mediaAsset) }}" class="btn btn-sm btn-secondary-custom">
                <i class="bi bi-download"></i>
            </a>
            @endif
        </li>
        @endforeach
    </ul>
</div>
@endif

@endsection