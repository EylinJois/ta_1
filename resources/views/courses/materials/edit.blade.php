@extends('layouts.app')

@section('title', 'Edit Material')

@section('page-title', 'Edit Material')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/course.css') }}">
@endpush

@php
    // Pull the type-specific rows so the form fields below can be
    // pre-filled with what's actually stored for this material.
    $currentLink = $material->materialLink;
    $currentPage = $material->materialPage;
    $currentYoutube = $material->materialYoutube;
    $currentAsset = optional($material->materialAssets)->mediaAsset;

    // The controller only ever keeps a single unlock rule per material.
    $currentUnlockRule = $material->unlockRules->first();
    $currentUnlockValue = $currentUnlockRule && $currentUnlockRule->value !== null
        ? json_decode($currentUnlockRule->value, true)
        : null;
@endphp

@section('content')

<div class="container-fluid">

    <div class="table-card">

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
            <div class="courses-header mb-4">

                <a href="{{ route('sections.show', $section) }}"
                    class="btn btn-secondary-custom">
                    <i class="bi bi-arrow-left"></i>
                </a>

                <div>

                    <h4 class="mb-0">
                        Edit Material
                    </h4>

                    <small class="text-muted">
                        {{ $section->name }}
                    </small>

                </div>

            </div>

            <div class="card-body p-0">

                <div class="row">

                    {{-- ===================================
                             LEFT COLUMN
                        =================================== --}}
                    <div class="col-lg-5">

                        <div class="card shadow-sm border-0 mb-4">

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

                                        @foreach($materialTypes as $type)

                                        <option
                                            value="{{ $type->id }}"
                                            data-name="{{ $type->name }}"
                                            {{ $material->material_type_id == $type->id ? 'selected' : '' }}>

                                            {{ $type->name }}

                                        </option>

                                        @endforeach

                                    </select>

                                    <div class="form-text">
                                        Material type can't be changed after creation.
                                    </div>

                                    {{-- disabled selects don't submit, so we send the real value via a hidden input --}}
                                    <input type="hidden"
                                        name="material_type_id"
                                        value="{{ $material->material_type_id }}">

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

                        {{-- =========================
                                 ACCESS RULES
                            ========================= --}}

                        <div class="card shadow-sm border-0">

                            <div class="card-header bg-white">

                                <h5 class="mb-0">
                                    Unlock Rules
                                </h5>

                            </div>

                            <div class="card-body">

                                <div class="mb-3">

                                    <label class="form-label">
                                        Unlock Rule
                                    </label>

                                    <select
                                        id="unlockRuleType"
                                        name="unlock_type_id"
                                        class="form-select">

                                        <option value="">
                                            None
                                        </option>

                                        @foreach($unlockRuleTypes as $rule)

                                        <option
                                            value="{{ $rule->id }}"
                                            data-code="{{ $rule->code }}"
                                            {{ old('unlock_type_id', optional($currentUnlockRule)->unlock_type_id) == $rule->id ? 'selected' : '' }}>

                                            {{ $rule->name }}

                                        </option>

                                        @endforeach

                                    </select>

                                </div>

                                <div id="unlockFields">

                                    <div id="unlockTargetContainer"
                                        class="d-none">

                                        <div class="mb-3">

                                            <label class="form-label" id="unlockTargetLabel">
                                                Only accessible to
                                            </label>

                                            <select
                                                id="unlockTarget"
                                                name="target_id"
                                                class="form-select">

                                            </select>

                                        </div>

                                    </div>

                                    <div id="unlockValueContainer" class="d-none">

                                        <label class="form-label" id="unlockValueLabel">
                                            Value
                                        </label>

                                        <input
                                            id="unlockText"
                                            type="text"
                                            name="unlock_value"
                                            class="form-control d-none">

                                        <input
                                            id="unlockNumber"
                                            type="number"
                                            name="unlock_value"
                                            class="form-control d-none">

                                        <input
                                            id="unlockDateTime"
                                            type="datetime-local"
                                            name="unlock_value"
                                            class="form-control d-none">

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                    {{-- ===================================
                             RIGHT COLUMN
                        =================================== --}}

                    <div class="col-lg-7">

                        <div class="card shadow-sm border-0">

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
                                    <label class="form-label">
                                        Page Content
                                    </label>

                                    <textarea
                                        id="pageContent"
                                        name="page_content"
                                        rows="18"
                                        class="form-control">{{ old('page_content', $currentPage->content ?? null) }}</textarea>

                                    <div class="form-text">

                                        HTML formatting is supported.

                                    </div>
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

            <div class="d-flex justify-content-end gap-2 mt-4">

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
        /*=================================================
            TINYMCE
        =================================================*/

        if (document.getElementById("pageContent")) {

            tinymce.init({
                selector: "#pageContent",
                height: 500,
                menubar: true,
                plugins: "lists link fullscreen",
                toolbar: "undo redo | blocks | bold italic | code"
            });

        }


        /*=================================================
            UNLOCK RULES
        =================================================*/

        const unlockType = document.querySelector("#unlockRuleType");

        const targetContainer = document.querySelector("#unlockTargetContainer");

        const valueContainer = document.querySelector("#unlockValueContainer");

        const target = document.querySelector("#unlockTarget");
        const targetLabel = document.querySelector("#unlockTargetLabel");

        const unlockTextInput = document.getElementById("unlockText");
        const unlockNumberInput = document.getElementById("unlockNumber");
        const unlockDateTimeInput = document.getElementById("unlockDateTime");

        function hideUnlockInputs() {
            unlockTextInput.classList.add("d-none");
            unlockNumberInput.classList.add("d-none");
            unlockDateTimeInput.classList.add("d-none");
        }

        // Existing stored values for this material's unlock rule (if any),
        // used to pre-select the target and pre-fill the value field.
        const existingTargetId = @json(old('target_id', optional($currentUnlockRule)->target_id));
        const existingUnlockValue = @json(old('unlock_value', $currentUnlockValue));

        function applyUnlockRule(code, prefill = false) {

            target.innerHTML = "";
            targetContainer.classList.add("d-none");
            valueContainer.classList.add("d-none");
            hideUnlockInputs();

            if (!code) {
                return;
            }

            switch (code.trim()) {
                case "role":
                    targetContainer.classList.remove("d-none");

                    targetLabel.textContent = "Only accessible to";

                    let roleOptions = `@foreach($roles as $roleId => $roleName)
        <option value="{{ $roleId }}">{{ $roleName }}</option>
    @endforeach`;

                    target.innerHTML = roleOptions;

                    if (prefill && existingTargetId) {
                        target.value = existingTargetId;
                    }
                    break;


                case "completed_activity":
                    targetContainer.classList.remove("d-none");

                    let materialOptions = `@foreach($materials as $materialOption)<option value="{{ $materialOption->id }}">{{ $materialOption->name }}</option>@endforeach`;
                    target.innerHTML = materialOptions;
                    targetLabel.textContent = "Only accessible after completing";

                    if (prefill && existingTargetId) {
                        target.value = existingTargetId;
                    }
                    break;


                case "available_at":
                    valueContainer.classList.remove("d-none");
                    unlockDateTimeInput.classList.remove("d-none");
                    targetLabel.textContent = "Only available after";

                    if (prefill && existingUnlockValue) {
                        unlockDateTimeInput.value = existingUnlockValue;
                    }
                    break;

                case "score":
                    valueContainer.classList.remove("d-none");
                    unlockNumberInput.classList.remove("d-none");

                    if (prefill && existingUnlockValue) {
                        unlockNumberInput.value = existingUnlockValue;
                    }
                    break;

                default:
                    valueContainer.classList.remove("d-none");
                    unlockTextInput.classList.remove("d-none");

                    if (prefill && existingUnlockValue) {
                        unlockTextInput.value = existingUnlockValue;
                    }
            }
        }

        unlockType.addEventListener("change", function() {

            if (this.selectedIndex === 0) {
                applyUnlockRule(null);
                return;
            }

            const code =
                this.options[this.selectedIndex].dataset.code;

            applyUnlockRule(code);
        });

        // Pre-fill the unlock rule fields for the material's existing rule.
        if (unlockType.selectedIndex > 0) {

            const initialCode =
                unlockType.options[unlockType.selectedIndex].dataset.code;

            applyUnlockRule(initialCode, true);
        }
    });
</script>
@endpush

@endsection