<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Note;
use App\Models\NoteFile;
use App\Models\MediaAsset;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Material;

class NoteController extends Controller
{
    public function saveMaterialMemo(Request $request, Material $material)
    {
        $this->authorize('view', $material);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'pinned' => 'sometimes|boolean',
        ]);

        $memo = $material->notes()
            ->where('user_id', auth()->id())
            ->orderByDesc('updated_at')
            ->first();

        if (!$memo) {
            $memo = new Note([
                'user_id' => auth()->id(),
            ]);
            $material->notes()->save($memo);
        }

        $memo->update([
            'title' => $validated['title'],
            'content' => $validated['content'] ?? null,
            'pinned' => $request->boolean('pinned'),
        ]);

        return redirect()
            ->route('materials.show', $material)
            ->with('success', 'Your material memo was saved.');
    }

    public function deleteMaterialMemo(Material $material)
    {
        $this->authorize('view', $material);

        $memo = $material->notes()
            ->where('user_id', auth()->id())
            ->orderByDesc('updated_at')
            ->firstOrFail();
        $memo->delete();

        return redirect()
            ->route('materials.show', $material)
            ->with('success', 'Your material memo was deleted.');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $notes = Note::where('user_id', auth()->id())
            ->with(['material.section.course'])
            ->orderByDesc('pinned')
            ->orderByDesc('updated_at')
            ->get();

        return view('notes.index', compact('notes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
{
    return view('notes.form', [
        'note' => new Note(),
        'materials' => $this->availableMaterials(),
    ]);
}

public function edit(Note $note)
{
    abort_unless($note->user_id === auth()->id(), 403);
    $note->load('noteFiles.mediaAsset');

    return view('notes.form', [
        'note' => $note,
        'materials' => $this->availableMaterials(),
    ]);
}



    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $note = Note::findOrFail($id);

        abort_unless($note->user_id === auth()->id(), 403);

        DB::transaction(function () use ($note) {
            foreach ($note->noteFiles as $file) {
                if ($file->mediaAsset) {
                    Storage::disk($file->mediaAsset->storage_disk)->delete($file->mediaAsset->file_path);
                    $file->mediaAsset->delete();
                }
                $file->delete();
            }

            $note->delete();
        });

        return redirect()
            ->route('notes.index')
            ->with('success', 'Note deleted successfully.');
    }

    public function pin(Note $note)
    {
        abort_unless($note->user_id === auth()->id(), 403);

        $note->update(['pinned' => !$note->pinned]);

        return response()->json(['status' => 'ok', 'pinned' => $note->pinned]);
    }

    public function editData(Note $note)
    {
        abort_unless($note->user_id === auth()->id(), 403);

        $note->load(['noteFiles.mediaAsset']);

        return response()->json([
            'id' => $note->id,
            'title' => $note->title,
            'content' => $note->content,
            'pinned' => $note->pinned,
            'material_id' => $note->material_id,
            'files' => $note->noteFiles->filter(fn($f) => $f->mediaAsset)->map(fn($f) => [
                'id' => $f->id,
                'original_name' => $f->mediaAsset->original_name,
                'url' => url('storage/' . ltrim($f->mediaAsset->file_path, '/')),
            ])->values(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'material_id' => 'nullable|integer|exists:materials,id',
            'photos' => 'nullable|array',
            'photos.*' => 'image|max:10240', 
        ]);

        if (!empty($validated['material_id'])) {
            $this->authorize('view', Material::findOrFail($validated['material_id']));
        }

        $note = DB::transaction(function () use ($request, $validated) {

            $note = Note::create([
                'title' => $validated['title'],
                'content' => $validated['content'] ?? null,
                'pinned' => $request->boolean('pinned'),
                'user_id' => auth()->id(),
                'material_id' => $validated['material_id'] ?: null,
            ]);

            $this->attachPhotos($request, $note);


            return $note;
        });

        if ($request->wantsJson()) {
            return response()->json(['note' => $note->fresh()], 201);
        }

        return redirect()
            ->route('notes.edit', ['note' => $note->id])
            ->with('success', 'Note created successfully.');
    }

    public function update(Request $request, Note $note)
    {
        abort_unless($note->user_id === auth()->id(), 403);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'material_id' => 'nullable|integer|exists:materials,id',
            'photos' => 'nullable|array',
            'photos.*' => 'image|max:10240',
            'remove_file_ids' => 'nullable|array',
            'remove_file_ids.*' => 'exists:note_files,id',
        ]);

        if (!empty($validated['material_id'])) {
            $this->authorize('view', Material::findOrFail($validated['material_id']));
        }

        DB::transaction(function () use ($request, $validated, $note) {

            $note->update([
                'title' => $validated['title'],
                'content' => $validated['content'] ?? null,
                'pinned' => $request->boolean('pinned'),
                'material_id' => $validated['material_id'] ?: null,
            ]);

            if (!empty($validated['remove_file_ids'])) {
                $filesToRemove = $note->noteFiles()->whereIn('id', $validated['remove_file_ids'])->get();

                foreach ($filesToRemove as $file) {
                    if ($file->mediaAsset) {
                        Storage::disk($file->mediaAsset->storage_disk)->delete($file->mediaAsset->file_path);
                        $file->mediaAsset->delete();
                    }
                    $file->delete();
                }
            }

            $this->attachPhotos($request, $note);
        });

        if ($request->wantsJson()) {
            return response()->json(['note' => $note->fresh()]);
        }

        return redirect()
            ->route('notes.edit', ['note' => $note->id])
            ->with('success', 'Note updated successfully.');
    }

    private function availableMaterials()
    {
        $user = auth()->user();
        $courseIds = $user->classes()->pluck('classes.course_id')->unique();

        $managerCourseIds = $user->managedClasses()
            ->pluck('classes.course_id')
            ->unique();

        return Material::whereIn('section_id', function ($query) use ($courseIds) {
                $query->select('id')
                    ->from('sections')
                    ->whereIn('course_id', $courseIds);
            })
            ->where(function ($query) use ($managerCourseIds) {
                $query->where('visibility', true);

                if ($managerCourseIds->isNotEmpty()) {
                    $query->orWhereHas('section', function ($sectionQuery) use ($managerCourseIds) {
                        $sectionQuery->whereIn('course_id', $managerCourseIds);
                    });
                }
            })
            ->with(['section.course'])
            ->orderBy('name')
            ->get();
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
            ]);
        }
    }
}
