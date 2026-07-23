<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Folder;
use App\Models\Material;
use App\Models\Note;
use App\Models\NoteFile;
use App\Models\MediaAsset;
use App\Models\Section;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class NoteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $currentFolderId = request('folder');

        $currentFolder = $currentFolderId
            ? Folder::findOrFail($currentFolderId)
            : null;

        $breadcrumbs = collect();

        if ($currentFolder) {
            $folder = $currentFolder;

            while ($folder) {
                $breadcrumbs->prepend($folder);
                $folder = $folder->parent;
            }

            $subfolders = Folder::where('parent_folder_id', $currentFolder->id)->get();
            $notes = Note::where('folder_id', $currentFolder->id)->get();
        } else {
            $subfolders = Folder::whereNull('parent_folder_id')->get();
            $notes = Note::whereNull('folder_id')->get();
        }

        return view('notes.index', compact(
            'currentFolder',
            'breadcrumbs',
            'subfolders',
            'notes'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $folder = Folder::where('id', request('folder'))->first() ?? null;
        $allFolders = Folder::where('user_id', auth()->id())->get();

        $courseIds = Auth::user()->courses()->pluck('courses.id');
        $sectionIds = Section::whereIn('course_id', $courseIds)
            ->pluck('id');
        $materials = Material::whereIn('section_id', $sectionIds)
            ->where('visibility', 1)
            ->get();

        return view('notes.create', compact('folder', 'allFolders', 'materials'));
    }



    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function pin(string $id) {}

    /**
     * Small dataset the modal fetches once (cached client-side) to populate
     * the Folder <select> and the Materials checkbox list.
     */
    public function formData()
    {
        $folders = Folder::where('user_id', auth()->id())
            ->orderBy('name')
            ->get()
            ->map(fn($f) => [
                'id' => $f->id,
                'name' => $f->name,
                'breadcrumb' => $f->name,
            ]);

        $materials = Material::orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($m) => ['id' => $m->id, 'name' => $m->name]);

        return response()->json([
            'folders' => $folders,
            'materials' => $materials,
        ]);
    }

    /**
     * Note data + attached photo URLs + linked material ids, for pre-filling
     * the edit modal.
     */
    public function editData(Note $note)
    {
        abort_unless($note->user_id === auth()->id(), 403);

        $note->load(['files.mediaAsset', 'materials']);

        return response()->json([
            'id' => $note->id,
            'title' => $note->title,
            'content' => $note->content,
            'folder_id' => $note->folder_id,
            'pinned' => $note->pinned,
            'material_ids' => $note->materials->pluck('id'),
            'files' => $note->files->filter(fn($f) => $f->mediaAsset)->map(fn($f) => [
                'id' => $f->id,
                'original_name' => $f->original_name,
                'url' => Storage::disk($f->mediaAsset->storage_disk)->url($f->mediaAsset->file_path),
            ])->values(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'folder_id' => 'nullable|exists:folders,id',
            'photos' => 'nullable|array',
            'photos.*' => 'image|max:10240', // 10MB per photo
            'material_ids' => 'nullable|array',
            'material_ids.*' => 'exists:materials,id',
        ]);

        if (!empty($validated['folder_id'])) {
            $ownsFolder = Folder::where('id', $validated['folder_id'])
                ->where('user_id', auth()->id())
                ->exists();

            abort_unless($ownsFolder, 403);
        }

        $note = DB::transaction(function () use ($request, $validated) {

            $note = Note::create([
                'title' => $validated['title'],
                'content' => $validated['content'] ?? null,
                'folder_id' => $validated['folder_id'] ?? null,
                'pinned' => $request->boolean('pinned'),
                'user_id' => auth()->id(),
            ]);

            $this->attachPhotos($request, $note);

            if (!empty($validated['material_ids'])) {
                $note->materials()->sync($validated['material_ids']);
            }

            return $note;
        });

        if ($request->wantsJson()) {
            return response()->json(['note' => $note->fresh()], 201);
        }

        return redirect()
            ->route('notes.index', $note->folder_id ? ['folder' => $note->folder_id] : [])
            ->with('success', 'Note created successfully.');
    }

    public function update(Request $request, Note $note)
    {
        abort_unless($note->user_id === auth()->id(), 403);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'folder_id' => 'nullable|exists:folders,id',
            'photos' => 'nullable|array',
            'photos.*' => 'image|max:10240',
            'material_ids' => 'nullable|array',
            'material_ids.*' => 'exists:materials,id',
            'remove_file_ids' => 'nullable|array',
            'remove_file_ids.*' => 'exists:note_files,id',
        ]);

        if (!empty($validated['folder_id'])) {
            $ownsFolder = Folder::where('id', $validated['folder_id'])
                ->where('user_id', auth()->id())
                ->exists();

            abort_unless($ownsFolder, 403);
        }

        DB::transaction(function () use ($request, $validated, $note) {

            $note->update([
                'title' => $validated['title'],
                'content' => $validated['content'] ?? null,
                'folder_id' => $validated['folder_id'] ?? null,
                'pinned' => $request->boolean('pinned'),
            ]);

            if (!empty($validated['remove_file_ids'])) {
                $filesToRemove = $note->files()->whereIn('id', $validated['remove_file_ids'])->get();

                foreach ($filesToRemove as $file) {
                    if ($file->mediaAsset) {
                        Storage::disk($file->mediaAsset->storage_disk)->delete($file->mediaAsset->file_path);
                        $file->mediaAsset->delete();
                    }
                    $file->delete();
                }
            }

            $this->attachPhotos($request, $note);

            $note->materials()->sync($validated['material_ids'] ?? []);
        });

        if ($request->wantsJson()) {
            return response()->json(['note' => $note->fresh()]);
        }

        return redirect()
            ->route('notes.index', $note->folder_id ? ['folder' => $note->folder_id] : [])
            ->with('success', 'Note updated successfully.');
    }

    /**
     * Shared by store() and update(): saves each uploaded photo as a
     * MediaAsset + NoteFile row.
     */
    private function attachPhotos(Request $request, Note $note): void
    {
        if (!$request->hasFile('photos')) {
            return;
        }

        foreach ($request->file('photos') as $photo) {

            $path = $photo->store('notes', 'public');

            $mediaAsset = MediaAsset::create([
                'storage_disk' => 'public',
                'file_path' => $path,
                'original_name' => $photo->getClientOriginalName(),
                'mime' => $photo->getMimeType(),
                'size' => $photo->getSize(),
                'uploaded_by' => auth()->id(),
            ]);

            NoteFile::create([
                'note_id' => $note->id,
                'media_asset_id' => $mediaAsset->id,
                'original_name' => $photo->getClientOriginalName(),
            ]);
        }
    }
}
