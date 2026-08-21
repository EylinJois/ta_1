<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MaterialType;
use App\Models\Material;
use App\Models\MaterialLink;
use App\Models\MaterialPage;
use App\Models\MaterialYoutube;
use App\Models\MaterialAsset;
use App\Models\MediaAsset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Section;
class MaterialController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Section $section)
    {
        $materialTypes = MaterialType::all();
        $roles = [
            1 => 'Admin',
            2 => 'Teacher',
            3 => 'Student'
        ];
        $materials = $section->materials()->with('materialType')->get();

        return view('courses.materials.create', [
            'section' => $section,
            'course' => $section->course,
            'materialTypes' => $materialTypes,
            'roles' => $roles,
            'materials' => $materials
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|max:255',
            'description' => 'nullable',
            'material_type_id' => 'required|exists:material_types,id',
            'section_id' => 'required|exists:sections,id',
            'visibility' => 'required|boolean',

            'link_url' => 'nullable|url',
            'youtube_url' => 'nullable|url',
            'page_content' => 'nullable',
            'material_file' => 'nullable|file|max:51200',
            'pdf_file' => 'nullable|mimes:pdf|max:51200',
            'epub_file' => 'nullable|mimes:epub|max:51200',
        ]);

        DB::transaction(function () use ($request) {

            $material = Material::create([

                'name' => $request->name,

                'description' => $request->description,

                'material_type_id' => $request->material_type_id,

                'created_by' => auth()->id(),

                'visibility' => $request->visibility,

                'section_id' => $request->section_id

            ]);

            $type = MaterialType::find($request->material_type_id);

            switch ($type->name) {

                case 'Link':

                    MaterialLink::create([

                        'material_id' => $material->id,

                        'url' => $request->link_url

                    ]);

                    break;

                case 'Page':

                    MaterialPage::create([

                        'material_id' => $material->id,

                        'content' => $request->page_content

                    ]);

                    break;

                case 'Youtube Video':

                    MaterialYoutube::create([

                        'material_id' => $material->id,

                        'url' => $request->youtube_url,

                        'video_id' => $this->extractYoutubeId($request->youtube_url),

                        'start_sec' => $request->start_sec,

                        'end_sec' => $request->end_sec

                    ]);

                    break;

                case 'File':

                    $this->storeUploadedFile(
                        $request->file('material_file'),
                        $material
                    );

                    break;

                case 'PDF':

                    $this->storeUploadedFile(
                        $request->file('pdf_file'),
                        $material
                    );

                    break;

                case 'EPUB':

                    $this->storeUploadedFile(
                        $request->file('epub_file'),
                        $material
                    );

                    break;
            }
        });

        return redirect()->route('sections.show',  $request->section_id);
    }

    private function extractYoutubeId($url)
    {
        preg_match(
            '/(?:youtu\.be\/|v=)([^&]+)/',
            $url,
            $matches
        );

        return $matches[1] ?? null;
    }


    public function download(MediaAsset $asset)
    {
        $path = Storage::disk($asset->storage_disk)->path($asset->file_path);

        return response()->download(
            $path,
            $asset->original_name
        );
    }

    private function storeUploadedFile($file, Material $material)
    {
        if (!$file) {
            return;
        }

        // Store file in storage/app/public/materials
        $path = $file->store('materials', 'public');

        // Save media asset
        $asset = MediaAsset::create([
            'storage_disk' => 'public',
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => auth()->id(),
        ]);

        // Link media asset to material
        MaterialAsset::create([
            'material_id' => $material->id,
            'media_asset_id' => $asset->id,
        ]);
    }

    /**
     * Remove every uploaded asset (file/PDF/EPUB) currently attached to a
     * material, deleting both the physical file on disk and the DB rows.
     * Used by update() when a new file replaces an old one, and by
     * destroy() when the material itself is removed.
     */
    private function deleteMaterialAssets(Material $material)
    {
        $materialAssets = $material->materialAsset()->with('mediaAsset')->get();

        foreach ($materialAssets as $materialAsset) {

            $mediaAsset = $materialAsset->mediaAsset;

            if ($mediaAsset) {

                Storage::disk($mediaAsset->storage_disk)
                    ->delete($mediaAsset->file_path);

                $mediaAsset->delete();
            }

            $materialAsset->delete();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Material $material)
    {
        $material->load([
            'section.course',
            'materialType',
            'materialYoutube',
            'materialPage',
            'materialLink',
            // 'materialAssets.mediaAsset',
        ]);

        $section = $material->section;
        $course = $section->course;
        $current = $section->materials
            ->sortBy('order_idx')
            ->values();

        $index =
            $current->search(fn($m) => $m->id == $material->id);

        $previous =
            $current[$index - 1] ?? null;

        $next =
            $current[$index + 1] ?? null;

        return view(
            'courses.materials.show',
            compact(
                'material',
                'section',
                'course',
                'previous',
                'next'
            )
        );
    }
    

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $material = Material::with([
            'materialType',
            'materialLink',
            'materialPage',
            'materialYoutube',
            'materialAsset.mediaAsset',
        ])->findOrFail($id);

        $section = Section::findOrFail($material->section_id);
        $materialTypes = MaterialType::all();
        $roles = [
            1 => 'Admin',
            2 => 'Teacher',
            3 => 'Student'
        ];
        $materials = $section->materials()->with('materialType')->get();

        return view('courses.materials.edit', [
            'section' => $section,
            'course' => $section->course,
            'materialTypes' => $materialTypes,
            'roles' => $roles,
            'materials' => $materials,
            'material' => $material
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * material_type_id is intentionally NOT accepted here - the type is
     * locked after creation (enforced in the edit view), so we look up the
     * material's existing type to know which content table to touch.
     */
    public function update(Request $request, string $id)
    {
        $material = Material::findOrFail($id);

        $request->validate([
            'name' => 'required|max:255',
            'description' => 'nullable',
            'section_id' => 'required|exists:sections,id',
            'visibility' => 'required|boolean',

            'link_url' => 'nullable|url',
            'youtube_url' => 'nullable|url',
            'page_content' => 'nullable',
            'material_file' => 'nullable|file|max:51200',
            'pdf_file' => 'nullable|mimes:pdf|max:51200',
            'epub_file' => 'nullable|mimes:epub|max:51200',
        ]);

        DB::transaction(function () use ($request, $material) {

            $material->update([

                'name' => $request->name,

                'description' => $request->description,

                'visibility' => $request->visibility,

                'section_id' => $request->section_id

            ]);

            $type = MaterialType::find($material->material_type_id);

            switch ($type->name) {

                case 'Link':

                    MaterialLink::updateOrCreate(
                        ['material_id' => $material->id],
                        ['url' => $request->link_url]
                    );

                    break;

                case 'Page':

                    MaterialPage::updateOrCreate(
                        ['material_id' => $material->id],
                        ['content' => $request->page_content]
                    );

                    break;

                case 'Youtube Video':

                    MaterialYoutube::updateOrCreate(
                        ['material_id' => $material->id],
                        [
                            'url' => $request->youtube_url,

                            'video_id' => $this->extractYoutubeId($request->youtube_url),

                            'start_sec' => $request->start_sec,

                            'end_sec' => $request->end_sec
                        ]
                    );

                    break;

                case 'File':

                    if ($request->hasFile('material_file')) {

                        $this->deleteMaterialAssets($material);

                        $this->storeUploadedFile(
                            $request->file('material_file'),
                            $material
                        );
                    }

                    break;

                case 'PDF':

                    if ($request->hasFile('pdf_file')) {

                        $this->deleteMaterialAssets($material);

                        $this->storeUploadedFile(
                            $request->file('pdf_file'),
                            $material
                        );
                    }

                    break;

                case 'EPUB':

                    if ($request->hasFile('epub_file')) {

                        $this->deleteMaterialAssets($material);

                        $this->storeUploadedFile(
                            $request->file('epub_file'),
                            $material
                        );
                    }

                    break;
            }
        });

        return back()->with(
            'success',
            'Material updated successfully.'
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $material = Material::findOrFail($id);

        DB::transaction(function () use ($material) {

            // Physical files + media asset rows
            $this->deleteMaterialAssets($material);

            // Type-specific content rows
            $material->materialLink()->delete();
            $material->materialPage()->delete();
            $material->materialYoutube()->delete();

            $material->delete();
        });

        return back()->with(
            'success',
            'Material deleted successfully.'
        );
    }
}