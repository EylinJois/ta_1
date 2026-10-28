@extends('layouts.app')

@section('title', 'Edit Material')

@section('page-title', 'Edit Material')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/course.css') }}">
@endpush

@php
$currentLink = $material->materialLink;
$currentPage = $material->materialPage;
$currentYoutube = $material->materialYoutube;
$currentAsset = optional($material->materialAsset)->mediaAsset;
@endphp

@section('content')

<div class="container-fluid">

    <div class="table-card material-create-page">

        <form action="{{ route('materials.update', $material) }}"
            method="POST"
            enctype="multipart/form-data">

            @csrf
            @method('PUT')

            <input type="hidden"
                name="section_id"
                value="{{ $section->id }}">

            {{-- =========================
                     HEADER
                ========================= --}}
            <div class="courses-header material-create-header mb-4">

                <div>

                    <h4 class="mb-0">
                        Edit Material
                    </h4>

                    <small class="text-muted">
                        {{ $section->name }}
                    </small>

                </div>

                @if(session('success'))
                <div class="alert alert-success mb-0 ms-auto">
                    {{ session('success') }}
                </div>
                @endif

            </div>

            <div class="card-body p-0">

                <div class="row">

                    {{-- ===================================
                             LEFT COLUMN
                        =================================== --}}
                    <div class="col-lg-5">

                        <div class="card border-0 mb-4">

                            <div class="card-header bg-white">
                                <h5 class="mb-0">
                                    Basic Information
                                </h5>
                            </div>

                            <div class="card-body">

                                {{-- Name --}}
                                <div class="mb-3">

                                    <label class="form-label">
                                        Material Name
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        name="name"
                                        value="{{ old('name', $material->name) }}"
                                        required>

                                </div>

                                {{-- Description --}}
                                <div class="mb-3">

                                    <label class="form-label">
                                        Description
                                    </label>

                                    <textarea
                                        class="form-control"
                                        rows="4"
                                        name="description">{{ old('description', $material->description) }}</textarea>

                                </div>

                                {{-- Material Type (locked - cannot change type after creation) --}}
                                <div class="mb-3">
                                    <label class="form-label">
                                        Material Type
                                    </label>
                                    <select
                                        id="materialType"
                                        class="form-select"
                                        disabled>
                                        <option value="Link" data-name="Link" {{ $material->type === 'Link' ? 'selected' : '' }}>
                                            Link
                                        </option>
                                        <option value="Page" data-name="Page" {{ $material->type === 'Page' ? 'selected' : '' }}>
                                            Page
                                        </option>
                                        <option value="Youtube Video" data-name="Youtube Video" {{ $material->type === 'Youtube Video' ? 'selected' : '' }}>
                                            Youtube Video
                                        </option>
                                        <option value="File" data-name="File" {{ $material->type === 'File' ? 'selected' : '' }}>
                                            File
                                        </option>
                                        <option value="PDF" data-name="PDF" {{ $material->type === 'PDF' ? 'selected' : '' }}>
                                            PDF
                                        </option>
                                        <option value="EPUB" data-name="EPUB" {{ $material->type === 'EPUB' ? 'selected' : '' }}>
                                            EPUB
                                        </option>
                                    </select>
                                    <div class="form-text">
                                        Material type can't be changed after creation.
                                    </div>
                                    {{-- disabled selects don't submit, so we send the real value via a hidden input --}}
                                    <input type="hidden"
                                        name="type"
                                        value="{{ $material->type }}">
                                </div>

                                {{-- Visibility --}}
                                <div class="mb-3">

                                    <label class="form-label">
                                        Visibility
                                    </label>

                                    <select
                                        name="visibility"
                                        class="form-select">

                                        <option value="1" {{ (string) old('visibility', $material->visibility) === '1' ? 'selected' : '' }}>
                                            Visible
                                        </option>

                                        <option value="0" {{ (string) old('visibility', $material->visibility) === '0' ? 'selected' : '' }}>
                                            Hidden
                                        </option>

                                    </select>

                                </div>

                            </div>

                        </div>
                    </div>

                    {{-- ===================================
                             RIGHT COLUMN
                        =================================== --}}

                    <div class="col-lg-7">

                        <div class="card border-0">

                            <div class="card-header bg-white">

                                <h5 class="mb-0">
                                    Material Content
                                </h5>

                            </div>

                            <div class="card-body">

                                {{-- ===================================
     LINK
=================================== --}}
                                <div id="linkFields" class="d-none">
                                    <label class="form-label">
                                        Website URL
                                    </label>

                                    <input type="url"
                                        name="link_url"
                                        class="form-control"
                                        value="{{ old('link_url', $currentLink->url ?? null) }}"
                                        placeholder="https://example.com">

                                    <div class="form-text">
                                        Users will be redirected to this website.
                                    </div>
                                </div>
                                {{-- ===================================
     YOUTUBE
=================================== --}}
                                <div id="youtubeFields" class="d-none">
                                    <div class="mb-3">

                                        <label class="form-label">
                                            YouTube URL
                                        </label>

                                        <input
                                            type="url"
                                            id="youtubeUrl"
                                            name="youtube_url"
                                            class="form-control"
                                            value="{{ old('youtube_url', $currentYoutube->url ?? null) }}"
                                            placeholder="https://www.youtube.com/watch?v=...">

                                    </div>

                                    <input
                                        type="hidden"
                                        id="youtubeVideoId"
                                        name="youtube_video_id"
                                        value="{{ old('youtube_video_id', $currentYoutube->video_id ?? null) }}">

                                    <div class="row">

                                        <div class="col-md-6">

                                            <label class="form-label">
                                                Start Time (seconds)
                                            </label>

                                            <input
                                                type="number"
                                                class="form-control"
                                                name="start_sec"
                                                value="{{ old('start_sec', $currentYoutube->start_sec ?? null) }}"
                                                min="0">

                                        </div>

                                        <div class="col-md-6">

                                            <label class="form-label">
                                                End Time (seconds)
                                            </label>

                                            <input
                                                type="number"
                                                class="form-control"
                                                name="end_sec"
                                                value="{{ old('end_sec', $currentYoutube->end_sec ?? null) }}"
                                                min="0">

                                        </div>

                                    </div>

                                    <hr>

                                    <div id="youtubePreview"
                                        class="ratio ratio-16x9 d-none">

                                        <iframe
                                            id="youtubeIframe"
                                            allowfullscreen>
                                        </iframe>

                                    </div>
                                </div>
                                {{-- ===================================
     HTML PAGE
=================================== --}}
                                <div id="pageFields" class="d-none">

                                    <div class="mb-4">
                                        <label class="form-label">Intro Text</label>
                                        <textarea id="introText" name="intro_text" rows="4" class="form-control">{{ old('intro_text', $currentPage->content ?? '') }}</textarea>
                                        <div class="form-text">Shown before the sentence cards. HTML formatting is supported.</div>
                                    </div>

                                    <label class="form-label d-block">Sentences</label>
                                    <div id="sentenceList" class="mb-3"></div>

                                    <button type="button" id="addSentenceBtn" class="btn btn-secondary-custom btn-sm">
                                        <i class="bi bi-plus-circle me-1"></i> Add Sentence
                                    </button>

                                    <div id="dynamicFieldsContainer"></div>

                                </div>
                                {{-- ===================================
     FILE
=================================== --}}
                                <div id="fileFields" class="d-none">

                                    @if($currentAsset)
                                    <div class="mb-2">
                                        <span class="text-muted">Current file:</span>
                                        <a href="{{ Storage::disk($currentAsset->storage_disk)->url($currentAsset->file_path) }}" target="_blank">
                                            {{ $currentAsset->original_name }}
                                        </a>
                                    </div>
                                    @endif

                                    <div class="mb-3">

                                        <label class="form-label">

                                            Replace File

                                        </label>

                                        <input
                                            type="file"
                                            name="material_file"
                                            class="form-control">

                                        <div class="form-text">
                                            Leave empty to keep the current file.
                                        </div>

                                    </div>

                                    <div class="form-check">

                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            name="allow_download"
                                            value="1"
                                            checked>

                                        <label class="form-check-label">

                                            Allow students to download

                                        </label>

                                    </div>
                                </div>
                                {{-- ===================================
     PDF
=================================== --}}
                                <div id="pdfFields" class="d-none">

                                    @if($currentAsset)
                                    <div class="mb-2">
                                        <span class="text-muted">Current PDF:</span>
                                        <a href="{{ Storage::disk($currentAsset->storage_disk)->url($currentAsset->file_path) }}" target="_blank">
                                            {{ $currentAsset->original_name }}
                                        </a>
                                    </div>
                                    @endif

                                    <label class="form-label">

                                        Replace PDF

                                    </label>

                                    <input
                                        type="file"
                                        name="pdf_file"
                                        class="form-control"
                                        accept=".pdf">

                                    <div class="form-text">

                                        Leave empty to keep the current PDF. This PDF will be displayed inside the LMS.

                                    </div>
                                </div>
                                {{-- ===================================
     EPUB
=================================== --}}
                                <div id="epubFields" class="d-none">

                                    @if($currentAsset)
                                    <div class="mb-2">
                                        <span class="text-muted">Current EPUB:</span>
                                        <a href="{{ Storage::disk($currentAsset->storage_disk)->url($currentAsset->file_path) }}" target="_blank">
                                            {{ $currentAsset->original_name }}
                                        </a>
                                    </div>
                                    @endif

                                    <label class="form-label">

                                        Replace EPUB

                                    </label>

                                    <input
                                        type="file"
                                        name="epub_file"
                                        class="form-control"
                                        accept=".epub">

                                    <div class="form-text">

                                        Leave empty to keep the current EPUB. This EPUB will be opened with the built-in reader.

                                    </div>
                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

            {{-- =========================
                     FOOTER
                ========================= --}}

            <div class="d-flex justify-content-end gap-2 mt-4 material-create-actions">

                <a href="{{ route('sections.show', $section) }}"
                    class="btn btn-secondary-custom">

                    Cancel

                </a>

                <button
                    class="btn btn-theme"
                    type="submit">

                    <i class="bi bi-check-circle"></i>

                    Update Material

                </button>

            </div>

        </form>

    </div>
</div>

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {

        /*=================================================
            MATERIAL TYPE (locked - fields are shown based on
            the existing material's type, select is disabled)
        =================================================*/

        const materialType = document.getElementById("materialType");

        const sections = {
            Link: document.getElementById("linkFields"),
            "Youtube Video": document.getElementById("youtubeFields"),
            Page: document.getElementById("pageFields"),
            File: document.getElementById("fileFields"),
            PDF: document.getElementById("pdfFields"),
            EPUB: document.getElementById("epubFields")
        };

        function hideAllMaterialFields() {

            Object.values(sections).forEach(section => {

                section.classList.add("d-none");

            });

        }

        function showFieldsForCurrentType() {

            hideAllMaterialFields();

            if (!materialType) {
                return;
            }

            const selected =
                materialType.options[materialType.selectedIndex]?.dataset.name;

            if (sections[selected]) {
                sections[selected].classList.remove("d-none");
            }
        }

        // Since the type can't change, reveal the right fields once on load.
        showFieldsForCurrentType();

        /*=================================================
            YOUTUBE
        =================================================*/
        const youtubeInput = document.getElementById("youtubeUrl");
        const videoIdInput = document.getElementById("youtubeVideoId");
        const preview = document.getElementById("youtubePreview");
        const iframe = document.getElementById("youtubeIframe");

        function refreshYoutubePreview() {

            let id = "";

            try {

                const parsed = new URL(youtubeInput.value);

                if (parsed.hostname.includes("youtu.be")) {
                    id = parsed.pathname.substring(1);
                } else {
                    id = parsed.searchParams.get("v");
                }

            } catch (e) {
                id = "";
            }

            videoIdInput.value = id;

            if (id) {
                iframe.src = "https://www.youtube.com/embed/" + id;
                preview.classList.remove("d-none");
            } else {
                iframe.src = "";
                preview.classList.add("d-none");
            }
        }

        if (youtubeInput) {

            youtubeInput.addEventListener("input", refreshYoutubePreview);

            // Show the preview for the existing YouTube URL on load.
            if (youtubeInput.value) {
                refreshYoutubePreview();
            }
        }

        //page
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        const sentenceList = document.getElementById('sentenceList');
        const dynamicFieldsContainer = document.getElementById('dynamicFieldsContainer');
        const pageFieldsEl = document.getElementById('pageFields');
        const form = pageFieldsEl ? pageFieldsEl.closest('form') : null;

        let sentenceIndex = 0;

        function setPreviewLoading(card, isLoading) {
            const btn = card.querySelector('.preview-audio-btn');
            btn.disabled = isLoading;
            btn.innerHTML = isLoading ?
                `<span class="spinner-border spinner-border-sm"></span> Generating...` :
                `<i class="bi bi-play-fill"></i> Preview`;
        }

        function fetchTTS(card, hanzi) {
            setPreviewLoading(card, true);

            return fetch('{{ route("tts.generate") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        text: hanzi
                    }),
                })
                .then(r => {
                    if (!r.ok) throw new Error('TTS request failed');
                    return r.json();
                })
                .then(data => {
                    card.querySelector('.sentence-pinyin-preview').value = data.pinyin;

                    const audioSrc = data.type === 'stream' ?
                        `data:audio/mpeg;base64,${data.audio}` :
                        data.audio;

                    card.dataset.audioSrc = audioSrc;
                    card.dataset.audioForHanzi = hanzi;

                    return audioSrc;
                })
                .catch(() => {
                    alert('Could not generate audio. Please try again.');
                    return null;
                })
                .finally(() => setPreviewLoading(card, false));
        }

        function addSentenceCard(data = {}) {

            const idx = sentenceIndex++;

            const card = document.createElement('div');
            card.className = 'card mb-3 sentence-card';
            card.dataset.index = idx;

            card.innerHTML = `
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0">Chinese Sentence</h6>
                <button type="button" class="btn btn-sm btn-danger remove-sentence-btn">Delete</button>
            </div>

            <div class="mb-2">
                <label class="form-label">Chinese</label>
                <input type="text" class="form-control sentence-hanzi" value="${(data.hanzi || '').replace(/"/g, '&quot;')}">
            </div>

            <div class="mb-2">
                <label class="form-label">Pinyin <small class="text-muted">(auto-generated on save)</small></label>
                <input type="text" class="form-control sentence-pinyin-preview" value="${(data.pinyin || '').replace(/"/g, '&quot;')}" disabled>
            </div>

            <div class="mb-3">
                <label class="form-label">Translation</label>
                <input type="text" class="form-control sentence-meaning" value="${(data.meaning || '').replace(/"/g, '&quot;')}">
            </div>

            <button type="button" class="btn btn-secondary-custom btn-sm preview-audio-btn">
                <i class="bi bi-play-fill"></i> Preview
            </button>
        </div>
    `;

            card.querySelector('.remove-sentence-btn').addEventListener('click', function() {
                card.remove();
            });

            card.querySelector('.preview-audio-btn').addEventListener('click', function() {
                const hanzi = card.querySelector('.sentence-hanzi').value.trim();
                if (!hanzi) return;

                if (card.dataset.audioSrc && card.dataset.audioForHanzi === hanzi) {
                    new Audio(card.dataset.audioSrc).play();
                    return;
                }

                fetchTTS(card, hanzi).then(audioSrc => {
                    if (audioSrc) new Audio(audioSrc).play();
                });
            });

            card.querySelector('.sentence-hanzi').addEventListener('blur', function() {
                const hanzi = this.value.trim();
                if (!hanzi) return;
                fetchTTS(card, hanzi);
            });

            sentenceList.appendChild(card);
        }

        if (document.getElementById('addSentenceBtn')) {
            document.getElementById('addSentenceBtn').addEventListener('click', () => addSentenceCard());
        }

        // Pre-fill with existing sentences for this material
        @if($currentPage)
        @foreach($currentPage -> sentences as $sentence)
        addSentenceCard(@json(['hanzi' => $sentence -> hanzi, 'pinyin' => $sentence -> pinyin, 'meaning' => $sentence -> meaning]));
        @endforeach
        @endif

        if (form) {
            form.addEventListener('submit', function() {
                dynamicFieldsContainer.innerHTML = '';

                document.querySelectorAll('.sentence-card').forEach(function(card, i) {
                    const fields = {
                        hanzi: card.querySelector('.sentence-hanzi').value,
                        meaning: card.querySelector('.sentence-meaning').value,
                    };
                    Object.entries(fields).forEach(function([key, value]) {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = `sentences[${i}][${key}]`;
                        input.value = value;
                        dynamicFieldsContainer.appendChild(input);
                    });
                });
            });
        }

    });
</script>
@endpush

@endsection