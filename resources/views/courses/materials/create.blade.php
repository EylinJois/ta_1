@extends('layouts.app')

@section('title', 'Add Material')

@section('page-title', 'Add Material')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/course.css') }}">
@endpush

@section('content')

<div class="container-fluid">

    <div class="table-card">

        <form action="{{ route('materials.store') }}"
            method="POST"
            enctype="multipart/form-data">

            @csrf

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
                        Add Material
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
                                        name="description"></textarea>

                                </div>

                                {{-- Material Type --}}
                                <div class="mb-3">

                                    <label class="form-label">
                                        Material Type
                                    </label>

                                    <select
                                        id="materialType"
                                        name="material_type_id"
                                        class="form-select"
                                        required>

                                        <option value="">
                                            Select Type
                                        </option>

                                        @foreach($materialTypes as $type)

                                        <option
                                            value="{{ $type->id }}"
                                            data-name="{{ $type->name }}">

                                            {{ $type->name }}

                                        </option>

                                        @endforeach

                                    </select>

                                </div>

                                {{-- Visibility --}}
                                <div class="mb-3">

                                    <label class="form-label">
                                        Visibility
                                    </label>

                                    <select
                                        name="visibility"
                                        class="form-select">

                                        <option value="1">
                                            Visible
                                        </option>

                                        <option value="0">
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
                                            data-code="{{ $rule->code }}">

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
                                <div id="linkFields">
                                    <label class="form-label">
                                        Website URL
                                    </label>

                                    <input type="url"
                                        name="link_url"
                                        class="form-control"
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
                                            placeholder="https://www.youtube.com/watch?v=...">

                                    </div>

                                    <input
                                        type="hidden"
                                        id="youtubeVideoId"
                                        name="youtube_video_id">

                                    <div class="row">

                                        <div class="col-md-6">

                                            <label class="form-label">
                                                Start Time (seconds)
                                            </label>

                                            <input
                                                type="number"
                                                class="form-control"
                                                name="start_sec"
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
                                        class="form-control">

                                    </textarea>

                                    <div class="form-text">

                                        HTML formatting is supported.

                                    </div>
                                </div>
                                {{-- ===================================
     FILE
=================================== --}}
                                <div id="fileFields" class="d-none">
                                    <div class="mb-3">

                                        <label class="form-label">

                                            Upload File

                                        </label>

                                        <input
                                            type="file"
                                            name="material_file"
                                            class="form-control">

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
                                    <label class="form-label">

                                        Upload PDF

                                    </label>

                                    <input
                                        type="file"
                                        name="pdf_file"
                                        class="form-control"
                                        accept=".pdf">

                                    <div class="form-text">

                                        This PDF will be displayed inside the LMS.

                                    </div>
                                </div>
                                {{-- ===================================
     EPUB
=================================== --}}
                                <div id="epubFields" class="d-none">
                                    <label class="form-label">

                                        Upload EPUB

                                    </label>

                                    <input
                                        type="file"
                                        name="epub_file"
                                        class="form-control"
                                        accept=".epub">

                                    <div class="form-text">

                                        This EPUB will be opened with the built-in reader.

                                    </div>
                                </div>
                                {{-- Default Message --}}

                                <div
                                    id="selectMaterialType">

                                    <div class="text-center py-5">

                                        <i
                                            class="bi bi-folder2-open display-4 text-secondary">
                                        </i>

                                        <h5 class="mt-3">
                                            Select a Material Type
                                        </h5>

                                        <p class="text-muted">

                                            The appropriate fields
                                            will appear here.

                                        </p>

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

                    Save Material

                </button>

            </div>

        </form>

    </div>
</div>

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {

        /*=================================================
            MATERIAL TYPE
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

        const placeholder =
            document.getElementById("selectMaterialType");

        function hideAllMaterialFields() {

            Object.values(sections).forEach(section => {

                section.classList.add("d-none");

            });

            placeholder.classList.remove("d-none");

        }

        hideAllMaterialFields();

        if (materialType) {
            materialType.addEventListener("change", function() {

                hideAllMaterialFields();

                const selected =
                    this.options[this.selectedIndex].dataset.name;

                if (sections[selected]) {

                    placeholder.classList.add("d-none");

                    sections[selected].classList.remove("d-none");

                }

            });
        }

        /*=================================================
            YOUTUBE
        =================================================*/
        const youtubeInput = document.getElementById("youtubeUrl");

        if (youtubeInput) {

            youtubeInput.addEventListener("input", function() {

                const videoIdInput = document.getElementById("youtubeVideoId");
                const preview = document.getElementById("youtubePreview");
                const iframe = document.getElementById("youtubeIframe");

                let id = "";

                try {

                    const parsed = new URL(this.value);

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

            });

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

        const unlockNumberInput = document.getElementById("unlockNumber");
        const unlockDateTimeInput = document.getElementById("unlockDateTime");

        function hideUnlockInputs() {
            unlockNumberInput.classList.add("d-none");
            unlockDateTimeInput.classList.add("d-none");
        }

        // Builds the correct field set for a given unlock rule "code".
        // Centralised so both the change handler (create/edit) and the
        // initial pre-fill on the edit page can reuse the exact same logic.
        function applyUnlockRule(code) {

            target.innerHTML = "";
            targetContainer.classList.add("d-none");
            valueContainer.classList.add("d-none");
            hideUnlockInputs();

            if (!code) {
                return;
            }

            switch (code) {
                case "role":
                    targetContainer.classList.remove("d-none");

                    targetLabel.textContent = "Only accessible to";

                    let roleOptions = `@foreach($roles as $roleId => $roleName)
        <option value="{{ $roleId }}">{{ $roleName }}</option>
    @endforeach`;

                    target.innerHTML = roleOptions;
                    break;


                case "completed_activity":
                    targetContainer.classList.remove("d-none");

                    let materialOptions = `@foreach($materials as $material)<option value="{{ $material->id }}">{{ $material->name }}</option>@endforeach`;
                    target.innerHTML = materialOptions;
                    targetLabel.textContent = "Only accessible after completing";
                    break;


                case "available_at":
                    console.log("available_at selected");
                    valueContainer.classList.remove("d-none");
                    unlockDateTimeInput.classList.remove("d-none");
                    targetLabel.textContent = "Only available after";
                    break;

                case "score":
                    console.log("score selected");
                    valueContainer.classList.remove("d-none");
                    unlockNumberInput.classList.remove("d-none");
                    break;

                default:
                    valueContainer.classList.remove("d-none");
            }
        }

        unlockType.addEventListener("change", function() {

            if (this.selectedIndex === 0) {
                applyUnlockRule(null);
                return;
            }

            const code =
                this.options[this.selectedIndex].dataset.code;
            applyUnlockRule(code.trim());
        });
    });
</script>
@endpush

@endsection