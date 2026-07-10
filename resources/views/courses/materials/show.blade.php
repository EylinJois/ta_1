@extends('layouts.app')

@section('title', $material->name)

@section('page-title', 'Material')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/course.css') }}">
@endpush

@section('content')

@if(!$canAccess)

<div class="empty-state">

    <i class="bi bi-lock-fill"></i>

    <h3>

        Material Locked

    </h3>

    <p>

        You don't currently have permission to access this material.

    </p>

</div>

@else
<div class="course-detail-banner mb-4">

    <div class="row align-items-center">

        <div class="col-lg-8">

            <span class="course-badge">

                {{ $section->name }}

            </span>

            <h2>

                {{ $material->name }}

            </h2>

            <p>

                {{ $material->description }}

            </p>

            <div class="course-meta">

                <div class="meta-item">

                    <i class="bi bi-book"></i>

                    {{ $course->name }}

                </div>

                <div class="meta-item">

                    <i class="bi bi-file-earmark"></i>

                    {{ $material->materialType->name }}

                </div>

            </div>

        </div>

        <div class="col-lg-4">

            <img
                src="{{ asset('storage/'.$course->img) }}"
                class="detail-course-image">

        </div>

    </div>

</div>
<div class="mb-4">

    <a
        href="{{ route('sections.show',$section) }}"
        class="btn btn-secondary-custom">

        <i class="bi bi-arrow-left"></i>

        Back to Section

    </a>

</div>
<div class="card border-0 shadow-sm">

    <div class="card-body p-4">
        @if($material->materialType->name == 'Youtube Video')

        <div class="ratio ratio-16x9">

            <iframe

                src="https://www.youtube.com/embed/{{ $material->materialYoutube->video_id }}?start={{ $material->materialYoutube->start_sec ?? 0 }}"

                allowfullscreen>

            </iframe>

        </div>

        @endif
        @if($material->materialType->name == 'Page')

        <div class="html-page">

            {!! $material->materialPage->content !!}

        </div>

        @endif
        @if($material->materialType->name == 'Link')

        <div class="text-center py-5">

            <i class="bi bi-link-45deg display-1 text-primary"></i>

            <h3 class="mt-3">

                External Website

            </h3>

            <p>

                Click below to open this learning resource.

            </p>

            <a

                href="{{ $material->materialLink->url }}"

                target="_blank"

                class="btn btn-theme">

                Visit Website

            </a>

        </div>

        @endif
        @if($material->materialType->name == 'File')

        @php

        $asset = $material->assets->first();

        @endphp

        <div class="text-center py-5">

            <i class="bi bi-file-earmark-arrow-down display-1 text-danger"></i>

            <h3>

                Download Material

            </h3>

            <a

                href="{{ Storage::url($asset->mediaAsset->filepath) }}"

                class="btn btn-theme"

                download>

                Download File

            </a>

        </div>

        @endif

        @if($material->materialType->name == 'PDF')

        @php
        $pdf = $material->assets->first();
        @endphp

        <canvas id="pdfCanvas"></canvas>

        @endif

        @if($material->materialType->name == 'EPUB')

        @php
        $epub = $material->assets->first();
        @endphp

        <div
            id="epubViewer"
            style="height:80vh;">
        </div>

        @endif
    </div>

</div>

<div class="d-flex justify-content-between mt-5">

    <div>

        @if($previous)

        <a
            href="{{ route('materials.show',$previous) }}"
            class="btn btn-secondary-custom">

            ← Previous

        </a>

        @endif

    </div>

    <div>

        @if($next)

        <a
            href="{{ route('materials.show',$next) }}"
            class="btn btn-theme">

            Next →

        </a>

        @endif

    </div>

</div>
@push('scripts')
<script type="module">
    import * as pdfjsLib from "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.4.168/pdf.min.mjs";

    pdfjsLib.GlobalWorkerOptions.workerSrc =
        "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.4.168/pdf.worker.min.mjs";

        @if($material->materialType->name == 'PDF')
    const url =
        "{{ Storage::url($pdf->mediaAsset->filepath) }}";

    pdfjsLib.getDocument(url).promise.then(pdf => {

        pdf.getPage(1).then(page => {

            const viewport =
                page.getViewport({
                    scale: 1.4
                });

            const canvas =
                document.getElementById("pdfCanvas");

            const ctx =
                canvas.getContext("2d");

            canvas.width =
                viewport.width;

            canvas.height =
                viewport.height;

            page.render({

                canvasContext: ctx,

                viewport

            });

        });

    });
@endif

    @if($material->materialType->name == 'EPUB')
    var book =
        ePub("{{ Storage::url($epub->mediaAsset->filepath) }}");

    var rendition =
        book.renderTo("epubViewer", {

            width: "100%",

            height: "100%"

        });

    rendition.display();

    @endif
</script>
@endpush
@endif
@endsection