<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Folder;

class FolderController extends Controller
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
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required'
        ]);
        $folder = Folder::create([
            'name' => $request->name,
            'user_id' => auth()->user()->id,
            'parent_folder_id' => $request->parent_folder_id ?? null
        ]);

        return redirect()->route('notes.index', ['folder' => $folder->id]);
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
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $folder = Folder::where('id', $id)->first();
        $folder->name = $request->name;
        $folder->save();
        return redirect()->route('notes.index', ['folder' => $folder->id]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $folder = Folder::where('id', $id)->first();
        $folder->delete();
        return redirect()->route('notes.index', ['folder' => $folder->parent_folder_id]);
    }
}
