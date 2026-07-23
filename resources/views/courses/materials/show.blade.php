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
    <h3>Material Locked</h3>
    <p>You don't currently have permission to access this material.</p>
</div>

@else

<div class="mb-4">
    <a href="{{ route('sections.show',$section) }}" class="btn btn-secondary-custom">
        <i class="bi bi-arrow-left"></i>
        Back to Section
    </a>
</div>

<div class="card border-0 shadow-sm">

    <div class="card-body p-4">
        @if($material->materialType->name == 'Youtube Video')

        <div class="ratio ratio-16x9">
            <iframe
                id="ytPlayer"
                src="https://www.youtube.com/embed/{{ $material->materialYoutube->video_id }}?start={{ $material->materialYoutube->start_sec ?? 0 }}&enablejsapi=1"
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
            <h3 class="mt-3">External Website</h3>
            <p>Click below to open this learning resource.</p>

            id="visitLinkBtn"
            href="{{ $material->materialLink->url }}"
            target="_blank"
            class="btn btn-theme">
            Visit Website
            </a>
        </div>

        @endif
        @if($material->materialType->name == 'File')

        @php
        $asset = $material->materialAsset;
        @endphp

        <div class="text-center py-5">
            <i class="bi bi-file-earmark-arrow-down display-1 text-danger"></i>
            <h3>Download Material</h3>
            <a id="downloadFileBtn" href="{{ route('media.download', $asset->mediaAsset) }}" class="btn btn-theme">
                Download File
            </a>
        </div>

        @endif

        @if($material->materialType->name == 'PDF')

        @php
        $pdf = $material->materialAsset;
        @endphp

        <div class="pdf-viewer text-center">
            <div class="pdf-toolbar mb-3 d-flex align-items-center gap-2 justify-content-center">
                <button id="prevPage" class="btn btn-secondary btn-sm">Previous</button>
                <span>Page <span id="pageNum">1</span> / <span id="pageCount">0</span></span>
                <button id="nextPage" class="btn btn-secondary btn-sm">Next</button>
                <button id="zoomOut" class="btn btn-outline-secondary btn-sm">−</button>
                <span id="zoomLevel">100%</span>
                <button id="zoomIn" class="btn btn-outline-secondary btn-sm">+</button>
            </div>
            <div class="pdf-container border rounded bg-light">
                <canvas id="pdfCanvas"></canvas>
            </div>
        </div>

        @endif

        @if($material->materialType->name == 'EPUB')

        @php
        $epub = $material->materialAsset;
        @endphp

        <div class="epub-toolbar mb-3 d-flex align-items-center gap-2 justify-content-center">
            <button id="prevPage" class="btn btn-secondary btn-sm">Previous</button>
            <span>Page <span id="pageNum">1</span> / <span id="pageCount">0</span></span>
            <button id="nextPage" class="btn btn-secondary btn-sm">Next</button>
            <button id="zoomOut" class="btn btn-outline-secondary btn-sm">−</button>
            <span id="zoomLevel">100%</span>
            <button id="zoomIn" class="btn btn-outline-secondary btn-sm">+</button>
        </div>

        <div id="epubViewer" style="height:80vh;"></div>

        @endif
    </div>



<div class="d-flex justify-content-between align-items-center mt-5">

    <div>
        @if($previous)
        <a href="{{ route('materials.show',$previous) }}" class="btn btn-secondary-custom">
            ← Previous
        </a>
        @endif
    </div>

    <button id="markCompleteBtn" class="btn btn-outline-success">
        <i class="bi bi-check-circle"></i> Mark as Complete
    </button>

    <div>
        @if($next)
        <a href="{{ route('materials.show',$next) }}" class="btn btn-theme">
            Next →
        </a>
        @endif
    </div>
</div>


@push('scripts')

{{-- SHARED PROGRESS TRACKING — reports to /materials/{id}/progress on a
     15s heartbeat, on page leave, and on manual "Mark as Complete". Each
     material type below registers a percentGetter() that computes its own
     live progress; the shared code just polls it and reports the max. --}}
<script>
    (function() {
        const materialId = {
            {
                $material - > id
            }
        };
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        let secondsSinceLastSync = 0;
        let highestPercent = 0;
        let percentGetter = () => 0;

        setInterval(() => secondsSinceLastSync++, 1000);

        function sync(opts = {}) {
            const live = opts.forceComplete ? 100 : (percentGetter() || 0);
            highestPercent = Math.max(highestPercent, live);

            const payload = {
                progress_percent: highestPercent,
                duration_delta: secondsSinceLastSync,
            };
            if (opts.forceComplete) payload.force_complete = true;

            secondsSinceLastSync = 0;

            fetch(`/materials/${materialId}/progress`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(payload),
            }).catch(() => {});
        }

        setInterval(() => {
            if (document.hidden || secondsSinceLastSync === 0) return;
            sync();
        }, 15000);

        window.addEventListener('beforeunload', () => {
            if (secondsSinceLastSync > 0) {
                highestPercent = Math.max(highestPercent, percentGetter() || 0);
                navigator.sendBeacon(
                    `/materials/${materialId}/progress`,
                    new Blob(
                        [JSON.stringify({
                            progress_percent: highestPercent,
                            duration_delta: secondsSinceLastSync
                        })], {
                            type: 'application/json'
                        }
                    )
                );
            }
        });

        window.materialProgress = {
            setPercentGetter: (fn) => {
                percentGetter = fn;
            },
            sync,
        };

        document.getElementById('markCompleteBtn')?.addEventListener('click', function() {
            sync({
                forceComplete: true
            });
            this.disabled = true;
            this.innerHTML = '<i class="bi bi-check-circle-fill"></i> Completed';
        });
    })();
</script>

@if($material->materialType->name == 'Youtube Video')
<script src="https://www.youtube.com/iframe_api"></script>
<script>
    let ytPlayer;

    function onYouTubeIframeAPIReady() {
        ytPlayer = new YT.Player('ytPlayer', {});
        window.materialProgress.setPercentGetter(function() {
            if (!ytPlayer || typeof ytPlayer.getDuration !== 'function') return 0;
            const duration = ytPlayer.getDuration();
            if (!duration) return 0;
            return Math.round((ytPlayer.getCurrentTime() / duration) * 100);
        });
    }
</script>
@endif

@if($material->materialType->name == 'Page')
<script>
    (function() {
        window.materialProgress.setPercentGetter(function() {
            const scrollable = document.body.scrollHeight - window.innerHeight;
            if (scrollable <= 0) return 100;
            return Math.min(100, Math.round((window.scrollY / scrollable) * 100));
        });
    })();
</script>
@endif

@if($material->materialType->name == 'Link')
<script>
    (function() {
        let opened = 0;
        window.materialProgress.setPercentGetter(() => opened);
        document.getElementById('visitLinkBtn')?.addEventListener('click', function() {
            opened = 25;
            window.materialProgress.sync();
        });
    })();
</script>
@endif

@if($material->materialType->name == 'File')
<script>
    (function() {
        let downloaded = 0;
        window.materialProgress.setPercentGetter(() => downloaded);
        document.getElementById('downloadFileBtn')?.addEventListener('click', function() {
            downloaded = 50;
            window.materialProgress.sync();
        });
    })();
</script>
@endif

@if($material->materialType->name == 'PDF')
<script type="module">
    import * as pdfjsLib from "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.4.168/pdf.min.mjs";

    pdfjsLib.GlobalWorkerOptions.workerSrc =
        "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.4.168/pdf.worker.min.mjs";

    const url = "{{ Storage::url($pdf->mediaAsset->file_path) }}";

    let pdfDoc = null;
    let pageNum = 1;
    let pageRendering = false;
    let pageNumPending = null;
    let scale = 1.2;

    const canvas = document.getElementById("pdfCanvas");
    const ctx = canvas.getContext("2d");

    function renderPage(num) {
        pageRendering = true;

        pdfDoc.getPage(num).then(function(page) {
            const viewport = page.getViewport({
                scale: scale
            });
            canvas.height = viewport.height;
            canvas.width = viewport.width;

            const renderTask = page.render({
                canvasContext: ctx,
                viewport: viewport
            });

            renderTask.promise.then(function() {
                pageRendering = false;
                if (pageNumPending !== null) {
                    renderPage(pageNumPending);
                    pageNumPending = null;
                }
            });

            document.getElementById("pageNum").textContent = num;
            document.getElementById("zoomLevel").textContent = Math.round(scale * 100) + "%";

            // Report progress on every page turn, not just the 15s heartbeat.
            window.materialProgress.sync();
        });
    }

    function queueRenderPage(num) {
        if (pageRendering) {
            pageNumPending = num;
        } else {
            renderPage(num);
        }
    }

    function previousPage() {
        if (pageNum <= 1) return;
        pageNum--;
        queueRenderPage(pageNum);
    }

    function nextPage() {
        if (pageNum >= pdfDoc.numPages) return;
        pageNum++;
        queueRenderPage(pageNum);
    }

    function zoomIn() {
        scale += 0.2;
        queueRenderPage(pageNum);
    }

    function zoomOut() {
        if (scale <= 0.4) return;
        scale -= 0.2;
        queueRenderPage(pageNum);
    }

    pdfjsLib.getDocument(url).promise.then(function(pdf) {
        pdfDoc = pdf;
        document.getElementById("pageCount").textContent = pdf.numPages;

        window.materialProgress.setPercentGetter(function() {
            return Math.round((pageNum / pdfDoc.numPages) * 100);
        });

        renderPage(pageNum);
    });

    document.getElementById("prevPage").addEventListener("click", previousPage);
    document.getElementById("nextPage").addEventListener("click", nextPage);
    document.getElementById("zoomIn").addEventListener("click", zoomIn);
    document.getElementById("zoomOut").addEventListener("click", zoomOut);
</script>
@endif

@if($material->materialType->name == 'EPUB')
<script>
    const url = "{{ Storage::url($epub->mediaAsset->file_path) }}";

    const book = ePub(url);
    const rendition = book.renderTo("epubViewer", {
        width: "100%",
        height: "100%"
    });

    let fontSize = 100;

    rendition.display();

    book.ready
        .then(() => book.locations.generate(1000))
        .then(() => {
            document.getElementById("pageCount").textContent = book.locations.total;
            updatePage();
        });

    function updatePage() {
        const current = rendition.currentLocation();
        if (!current) return;
        const page = book.locations.locationFromCfi(current.start.cfi);
        document.getElementById("pageNum").textContent = page;

        window.materialProgress.sync();
    }

    window.materialProgress.setPercentGetter(function() {
        const current = rendition.currentLocation();
        if (!current || !book.locations || !book.locations.total) return 0;
        const page = book.locations.locationFromCfi(current.start.cfi);
        return Math.round((page / book.locations.total) * 100);
    });

    rendition.on("relocated", updatePage);

    document.getElementById("prevPage").addEventListener("click", () => rendition.prev());
    document.getElementById("nextPage").addEventListener("click", () => rendition.next());

    document.getElementById("zoomIn").addEventListener("click", function() {
        fontSize += 10;
        rendition.themes.fontSize(fontSize + "%");
        document.getElementById("zoomLevel").textContent = fontSize + "%";
    });

    document.getElementById("zoomOut").addEventListener("click", function() {
        if (fontSize <= 50) return;
        fontSize -= 10;
        rendition.themes.fontSize(fontSize + "%");
        document.getElementById("zoomLevel").textContent = fontSize + "%";
    });
</script>
@endif

@endpush
@endif
@endsection