@extends('layouts.app')

@section('title', $material->name)

@section('page-title', '' . $material->name)

@push('styles')
<link rel="stylesheet" href="{{ asset('css/course.css') }}">
@endpush

@section('content')

<div class="mb-4 d-flex justify-content-between align-items-center top-nav-row">
    <a href="{{ route('sections.show',$section) }}" class="btn btn-secondary-custom btn-icon-plain">
        <i class="bi bi-arrow-left"></i>
    </a>

    <button id="markCompleteBtn" class="btn btn-outline-success">
        <i class="bi bi-check-circle"></i> Mark as Complete
    </button>
</div>

<div class="card border-0 shadow-sm">

    <div class="card-body p-4">
        @if($material->type == 'Youtube Video')

        <div class="ratio ratio-16x9">
            <iframe
                id="ytPlayer"
                src="https://www.youtube.com/embed/{{ $material->materialYoutube->video_id }}?start={{ $material->materialYoutube->start_sec ?? 0 }}&enablejsapi=1"
                allowfullscreen>
            </iframe>
        </div>

        @endif
        @if($material->type == 'Page')

        @php
        $page = $material->materialPage;
        @endphp

        <div class="html-page">

            @if($page && $page->content)
            <div class="page-intro mb-4">
                {!! $page->content !!}
            </div>
            @endif

            <div id="sentenceViewerList">
                @forelse($page->sentences ?? [] as $sentence)
                <div class="card mb-3 sentence-view-card"
                    data-hanzi="{{ $sentence->hanzi }}">

                    <div class="card-body">

                        <p class="fs-4 mb-1">{{ $sentence->hanzi }}</p>
                        <p class="text-muted mb-2">{{ $sentence->pinyin }}</p>
                        <p class="mb-3">{{ $sentence->meaning }}</p>

                        <button type="button" class="btn btn-secondary-custom btn-sm play-sentence-btn">
                            <i class="bi bi-play-fill"></i> Play
                        </button>

                    </div>
                </div>
                @empty
                <p class="text-muted">No sentences have been added to this page yet.</p>
                @endforelse
            </div>

        </div>

        @endif
        @if($material->type == 'Link')

        <div class="text-center py-5">
            <i class="bi bi-link-45deg display-1 text-primary"></i>
            <h3 class="mt-3">External Website</h3>
            <p>Click below to open this learning resource.</p>

            <a
                id="visitLinkBtn"
                href="{{ $material->materialLink->url }}"
                target="_blank"
                class="btn btn-theme">
                Visit Website
            </a>
        </div>

        @endif
        @if($material->type == 'File')

        @php
        $asset = $material->materialAsset;
        @endphp

        <div class="text-center py-5">
            <i class="bi bi-file-earmark-arrow-down display-1 text-danger"></i>
            <h3>{{ $material->name }}</h3>
            <a id="downloadFileBtn" href="{{ route('media.download', $asset->mediaAsset) }}" class="btn btn-theme">
                Download    
            </a>
        </div>

        @endif

        @if($material->type == 'PDF')

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

        @if($material->type == 'EPUB')

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
</div>

<div class="d-flex justify-content-between align-items-center mt-5 material-nav-row">

    <div class="nav-prev">
        @if($previous)
        <a href="{{ route('materials.show',$previous) }}" class="btn btn-secondary-custom">
            ← Previous
        </a>
        @endif
    </div>

    <div class="nav-next">
        @if($next)
        <a href="{{ route('materials.show',$next) }}" class="btn btn-theme">
            Next →
        </a>
        @endif
    </div>
</div>

    @push('scripts')
    <script>
        (function() {
            const materialId = {
                {
                    $material -> id
                }
            };
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

            document.getElementById('markCompleteBtn')?.addEventListener('click', function() {

                const btn = this;
                btn.disabled = true;
                const originalHtml = btn.innerHTML;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';

                fetch(`/materials/${materialId}/progress`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({
                            progress_percent: 100,
                            force_complete: true
                        }),
                    })
                    .then(function(r) {
                        if (!r.ok) throw new Error();
                        btn.innerHTML = '<i class="bi bi-check-circle-fill"></i> Completed';
                    })
                    .catch(function() {
                        btn.disabled = false;
                        btn.innerHTML = originalHtml;
                        alert('Failed to mark as complete. Please try again.');
                    });
            });
        })();
    </script>

    @if($material->type == 'Youtube Video')
    <script src="https://www.youtube.com/iframe_api"></script>
    <script>
        let ytPlayer;

        function onYouTubeIframeAPIReady() {
            ytPlayer = new YT.Player('ytPlayer', {});
        }
    </script>
    @endif

    @if($material->type == 'PDF')
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

            renderPage(pageNum);
        });

        document.getElementById("prevPage").addEventListener("click", previousPage);
        document.getElementById("nextPage").addEventListener("click", nextPage);
        document.getElementById("zoomIn").addEventListener("click", zoomIn);
        document.getElementById("zoomOut").addEventListener("click", zoomOut);
    </script>
    @endif

    @if($material->type == 'EPUB')
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

        }

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

    @if($material->type == 'Page')
    <script>
        (function() {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

            document.querySelectorAll('.play-sentence-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    const card = btn.closest('.sentence-view-card');
                    const hanzi = card.dataset.hanzi;
                    if (!hanzi) return;

                    // Reuse cached audio if we've already generated it this page view
                    if (card.dataset.audioSrc) {
                        new Audio(card.dataset.audioSrc).play();
                        return;
                    }

                    btn.disabled = true;
                    const originalHtml = btn.innerHTML;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Loading...';

                    fetch('{{ route("tts.generate") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: JSON.stringify({
                                text: hanzi
                            }),
                        })
                        .then(function(r) {
                            if (!r.ok) throw new Error();
                            return r.json();
                        })
                        .then(function(data) {
                            const audioSrc = data.type === 'stream' ?
                                `data:audio/mpeg;base64,${data.audio}` :
                                data.audio;

                            card.dataset.audioSrc = audioSrc;
                            new Audio(audioSrc).play();
                        })
                        .catch(function() {
                            alert('Could not play audio. Please try again.');
                        })
                        .finally(function() {
                            btn.disabled = false;
                            btn.innerHTML = originalHtml;
                        });
                });
            });
        })();
    </script>
    @endif

    @endpush
    @endsection