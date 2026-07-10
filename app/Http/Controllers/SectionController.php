<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Section;

class SectionController extends Controller
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
            'name' => 'required',
            'visibility' => 'required',
            'course_id' => 'required',
        ]);

        $lastOrderIdx = Section::where('course_id', $request->course_id)
            ->max('order_idx');

        $request->merge([
            'order_idx' => $lastOrderIdx + 1
        ]);

        Section::create([
            'name' => $request->name,
            'visibility' => $request->visibility,
            'course_id' => $request->course_id,
            'order_idx' => $request->order_idx,
        ]);

        return back()->with(
            'success',
            'Section created successfully.'
        );
    }

    public function reorder(Request $request)
    {
        foreach ($request->sections as $section) {

            Section::where('id', $section['id'])
                ->update([
                    'order_idx' => $section['order_idx']
                ]);
        }

        return response()->json([
            'success' => true
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $section = Section::with('materials', 'course')
            ->findOrFail($id);

        $course = $section->course;

        $materialTypes = \App\Models\MaterialType::all();

        $unlockRuleTypes = \App\Models\UnlockRuleType::all();

        $roles = [
            'student' => 'Student',
            'teacher' => 'Teacher',
            'admin' => 'Admin',
        ];

        $materials = $section->materials()->get();

       

        return view('courses.sections.show', compact(
            'section',
            'course',
            'materialTypes',
            'unlockRuleTypes',
            'roles',
            'materials',
        
        ));
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
        $request->validate([
            'name' => 'required',
            'visibility' => 'required',
        ]);
        $section = Section::findOrFail($id);
        $section->update([
            'name' => $request->name,
            'visibility' => $request->visibility,
        ]);

        return back()->with(
            'success',
            'Section updated successfully.'
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $section = Section::findOrFail($id);
        $section->delete();

        return back()->with(
            'success',
            'Section deleted successfully.'
        );
    }
}
