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
        $validated = $request->validate([
            'name' => 'required',
            'visibility' => 'required',
            'course_id' => 'required|exists:courses,id',
        ]);

        $this->authorize('create', [Section::class, (int) $validated['course_id']]);

        $lastOrderIdx = Section::where('course_id', $validated['course_id'])
            ->max('order_idx');

        $request->merge([
            'order_idx' => $lastOrderIdx + 1
        ]);

        Section::create([
            'name' => $validated['name'],
            'visibility' => $validated['visibility'],
            'course_id' => $validated['course_id'],
            'order_idx' => $request->order_idx,
        ]);

        return back()->with(
            'success',
            'Section created successfully.'
        );
    }

    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'sections' => 'required|array',
            'sections.*.id' => 'required|exists:sections,id',
            'sections.*.order_idx' => 'required|integer',
        ]);

        $sections = collect($validated['sections'])->map(function ($section) {
            $record = Section::findOrFail($section['id']);
            $this->authorize('update', $record);
            return [$record, $section['order_idx']];
        });

        foreach ($sections as [$record, $orderIdx]) {
            $record->update(['order_idx' => $orderIdx]);
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
    $this->authorize('view', $section);

    $course = $section->course;


    $user = auth()->user();
    $isManager = $user->isManagerInCourse($course->id);

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
        $section = Section::findOrFail($id);
        $this->authorize('update', $section);

        $request->validate([
            'name' => 'required',
            'visibility' => 'required',
        ]);
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
        $this->authorize('delete', $section);
        $section->delete();

        return back()->with(
            'success',
            'Section deleted successfully.'
        );
    }
}
