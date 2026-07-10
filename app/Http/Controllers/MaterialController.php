<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MaterialType;
use App\Models\Material;
use App\Models\MaterialLink;
use App\Models\MaterialPage;
use App\Models\MaterialYoutube;
use App\Models\MaterialAsset;
use App\Models\MaterialUnlockRule;
use App\Models\MediaAsset;
use Illuminate\Support\Facades\DB;

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
    public function create()
    {
        $materialTypes = MaterialType::all();

        return view('materials.create', compact('materialTypes'));
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

            if ($request->filled('unlock_type_id')) {

                MaterialUnlockRule::create([
                    'material_id' => $material->id,
                    'unlock_type_id' => $request->unlock_type_id,
                    'target_id' => $request->target_id,
                    'value' => $request->unlock_value
                        ? json_encode($request->unlock_value)
                        : null
                ]);
            }
        });

        return back()->with(
            'success',
            'Material created successfully.'
        );
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

    private function storeUploadedFile($file, Material $material)
    {
        if (!$file) {
            return;
        }

        $path = $file->store(
            'materials',
            'public'
        );

        $asset = MediaAsset::create([
            'filename' => date('YmdHis') . '_' . $file->getClientOriginalName(),
            'filepath' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize()
        ]);

        MaterialAsset::create([
            'material_id' => $material->id,
            'media_asset_id' => $asset->id
        ]);
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
            'unlockRules.unlockType'

        ]);
        $canAccess = $this->canAccess($material);

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
                'canAccess','previous','next'
            )
        );
    }
    private function canAccess(Material $material)
    {
        $rules = $material->unlockRules;

        if ($rules->isEmpty()) {
            return true;
        }

        foreach ($rules as $rule) {
            switch ($rule->unlockType->name) {
                case 'role':
                    if (auth()->user()->role_id == $rule->target_id) {
                        return true;
                    }
                    break;

                default:
                    return true;
            }
        }
        return false;
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
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
}
