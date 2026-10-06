

<div class="modal fade" id="noteModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content custom-modal">

            <div class="modal-header border-0">
                <h4 class="modal-title" id="noteModalTitle">New Note</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form id="noteModalForm">
                @csrf

                <input type="hidden" id="noteModalNoteId" value="">

                <div class="modal-body">

                    <div id="noteModalError" class="alert alert-danger d-none"></div>

                    <div class="row">

                        {{-- MAIN --}}
                        <div class="col-lg-8">

                            <div class="mb-3">
                                <label class="form-label">Title</label>
                                <input type="text" name="title" id="noteModalTitleInput"
                                    class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Content</label>
                                <textarea name="content" id="noteModalContentInput"
                                    class="form-control" rows="8"></textarea>
                            </div>

                            <div id="noteModalExistingPhotosWrap" class="mb-3 d-none">
                                <label class="form-label">Current Photos</label>
                                <div id="noteModalExistingPhotos" class="photo-preview-grid"></div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" id="noteModalPhotoLabel">Photos</label>
                                <input type="file" name="photos[]" id="noteModalPhotoInput"
                                    class="form-control" accept="image/*" multiple>
                                <div id="noteModalPhotoPreview" class="photo-preview-grid mt-2"></div>
                            </div>

                        </div>

                        {{-- SIDEBAR --}}
                        <div class="col-lg-4">

                            <div class="note-form-sidebar">

                                <div class="mb-3">
                                    <label class="form-label">Folder</label>
                                    <select name="folder_id" id="noteModalFolderSelect" class="form-select">
                                        <option value="">No Folder</option>
                                    </select>
                                </div>

                                <div class="form-check mb-4">
                                    <input type="checkbox" class="form-check-input"
                                        id="noteModalPinnedInput" name="pinned" value="1">
                                    <label class="form-check-label" for="noteModalPinnedInput">
                                        <i class="bi bi-pin-angle-fill"></i>
                                        Pin this note
                                    </label>
                                </div>

                            </div>

                        </div>

                    </div>

                </div>

                <div class="modal-footer border-0">

                    <button type="button" class="btn btn-secondary-custom" data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit" class="btn btn-theme" id="noteModalSubmitBtn">
                        <i class="bi bi-check-circle"></i>
                        <span id="noteModalSubmitLabel">Save Note</span>
                    </button>

                </div>

            </form>

        </div>
    </div>
</div>

<script>
    (function() {

        const modalEl = document.getElementById('noteModal');
        const modal = new bootstrap.Modal(modalEl);
        const form = document.getElementById('noteModalForm');

        const titleEl = document.getElementById('noteModalTitle');
        const noteIdInput = document.getElementById('noteModalNoteId');
        const titleInput = document.getElementById('noteModalTitleInput');
        const contentInput = document.getElementById('noteModalContentInput');
        const folderSelect = document.getElementById('noteModalFolderSelect');
        const pinnedInput = document.getElementById('noteModalPinnedInput');

        const photoInput = document.getElementById('noteModalPhotoInput');
        const photoPreview = document.getElementById('noteModalPhotoPreview');
        const photoLabel = document.getElementById('noteModalPhotoLabel');
        const existingPhotosWrap = document.getElementById('noteModalExistingPhotosWrap');
        const existingPhotos = document.getElementById('noteModalExistingPhotos');

        const errorBox = document.getElementById('noteModalError');
        const submitBtn = document.getElementById('noteModalSubmitBtn');
        const submitLabel = document.getElementById('noteModalSubmitLabel');

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

        let formDataCache = null;
        let removedFileIds = [];

        async function loadFormData() {
            if (formDataCache) return formDataCache;
            const res = await fetch("{{ route('notes.form-data') }}", {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            formDataCache = await res.json();
            return formDataCache;
        }

        function renderFolderOptions(folders, selectedId) {
            folderSelect.innerHTML = '<option value="">No Folder</option>';
            folders.forEach(function(f) {
                const opt = document.createElement('option');
                opt.value = f.id;
                opt.textContent = f.breadcrumb || f.name;
                if (selectedId && String(selectedId) === String(f.id)) opt.selected = true;
                folderSelect.appendChild(opt);
            });
        }

        function renderExistingPhotos(files) {
            existingPhotos.innerHTML = '';
            removedFileIds = [];

            if (!files || !files.length) {
                existingPhotosWrap.classList.add('d-none');
                return;
            }

            existingPhotosWrap.classList.remove('d-none');

            files.forEach(function(file) {
                const thumb = document.createElement('div');
                thumb.className = 'photo-preview-thumb';
                thumb.dataset.fileId = file.id;
                thumb.innerHTML =
                    '<img src="' + file.url + '" alt="' + file.original_name + '">' +
                    '<label class="photo-remove-toggle">' +
                    '<input type="checkbox">' +
                    '<span><i class="bi bi-trash-fill"></i> Remove</span>' +
                    '</label>';

                thumb.querySelector('input[type="checkbox"]').addEventListener('change', function(e) {
                    thumb.classList.toggle('marked-for-removal', e.target.checked);
                    removedFileIds = e.target.checked
                        ? removedFileIds.concat([file.id])
                        : removedFileIds.filter(id => id !== file.id);
                });

                existingPhotos.appendChild(thumb);
            });
        }

        function renderPhotoPreview() {
            photoPreview.innerHTML = '';

            Array.from(photoInput.files).forEach(function(file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const thumb = document.createElement('div');
                    thumb.className = 'photo-preview-thumb';
                    thumb.innerHTML = '<img src="' + e.target.result + '" alt="' + file.name + '">';
                    photoPreview.appendChild(thumb);
                };
                reader.readAsDataURL(file);
            });
        }

        photoInput.addEventListener('change', renderPhotoPreview);

        function resetForm() {
            form.reset();
            noteIdInput.value = '';
            errorBox.classList.add('d-none');
            errorBox.innerHTML = '';
            photoPreview.innerHTML = '';
            existingPhotos.innerHTML = '';
            existingPhotosWrap.classList.add('d-none');
            removedFileIds = [];
        }

        window.openNoteModal = async function(options) {
            options = options || {};
            resetForm();

            const data = await loadFormData();

            if (options.mode === 'edit' && options.noteId) {

                titleEl.textContent = 'Edit Note';
                noteIdInput.value = options.noteId;
                submitLabel.textContent = 'Update Note';
                photoLabel.textContent = 'Add More Photos';

                modal.show();

                const res = await fetch('/notes/' + options.noteId + '/edit-data', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });
                const note = await res.json();

                titleInput.value = note.title;
                contentInput.value = note.content || '';
                pinnedInput.checked = !!note.pinned;

                renderFolderOptions(data.folders, note.folder_id);
                // renderMaterialOptions(data.materials, note.material_ids || []);
                renderExistingPhotos(note.files || []);

            } else {

                titleEl.textContent = 'New Note';
                submitLabel.textContent = 'Save Note';
                photoLabel.textContent = 'Photos of Physical Notes';

                renderFolderOptions(data.folders, options.folderId || null);
                // renderMaterialOptions(data.materials, options.materialId ? [options.materialId] : []);

                modal.show();
            }
        };

        form.addEventListener('submit', async function(e) {

            e.preventDefault();

            errorBox.classList.add('d-none');
            errorBox.innerHTML = '';
            submitBtn.disabled = true;

            const isEdit = !!noteIdInput.value;
            const url = isEdit ? '/notes/' + noteIdInput.value : "{{ route('notes.store') }}";

            const payload = new FormData(form);

            if (isEdit) {
                payload.append('_method', 'PUT');
                removedFileIds.forEach(id => payload.append('remove_file_ids[]', id));
            }

            try {

                const res = await fetch(url, {
                    method: 'POST', // Laravel method-spoofing handles PUT via the _method field above
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: payload,
                });

                const result = await res.json();

                if (!res.ok) {
                    errorBox.innerHTML = result.errors
                        ? Object.values(result.errors).flat().join('<br>')
                        : (result.message || 'Something went wrong.');
                    errorBox.classList.remove('d-none');
                    return;
                }

                modal.hide();
                document.dispatchEvent(new CustomEvent('note-saved', { detail: result.note }));

            } catch (err) {
                errorBox.textContent = 'Could not reach the server. Please try again.';
                errorBox.classList.remove('d-none');
            } finally {
                submitBtn.disabled = false;
            }
        });

    })();
</script>