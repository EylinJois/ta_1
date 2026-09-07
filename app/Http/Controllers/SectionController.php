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
    $section = Section::with([
        'course',
        'quizzes',
        'assignments',
    ])->findOrFail($id);

    $course = $section->course;


    $roles = [
        'student' => 'Student',
        'teacher' => 'Teacher',
        'admin' => 'Admin',
    ];

    $user = auth()->user();
    $isManager = $user->isAdmin($course->id) || $user->isTeacher($course->id);

    $materials = $section->materials;


    $visibleMaterials = $materials->filter(function ($material) use ($user, $isManager) {
        if ($isManager || auth()->id() == $material->created_by) {
            return true;
        }
        if (!$material->visibility) {
            return false;
        }
        return true;
    })->values();

    
    $visibleQuizzes = $section->quizzes
        ->filter(fn ($quiz) => $isManager || $quiz->visibility)
        ->map(function ($quiz) {
            $quiz->activity_kind = 'quiz';
            return $quiz;
        });

    $visibleAssignments = $section->assignments
    ->filter(function ($a) use ($isManager, $user) {
        if ($isManager || auth()->id() == $a->created_by) {
            return true;
        }
        if (!$a->visibility) {
            return false;
        }
        if ($a->opened_at && now()->lt($a->opened_at)) {
            return false;
        }
        return true;
    })
    ->map(function ($assignment) {
        $assignment->activity_kind = 'assignment';
        return $assignment;
    });

    // Merge both into one feed, newest first.
    $visibleActivities = $visibleQuizzes->concat($visibleAssignments)
        ->sortByDesc('created_at')
        ->values();

    $completedMaterialIds = $isManager
        ? collect()
        : \App\Models\MaterialCompletion::where('user_id', $user->id)
            ->whereIn('material_id', $visibleMaterials->pluck('id'))
            ->pluck('material_id');

    return view('courses.sections.show', compact(
        'section',
        'course',
        'roles',
        'materials',
        'visibleMaterials',
        'visibleActivities',
        'visibleQuizzes',
        'visibleAssignments',
        'completedMaterialIds'
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
