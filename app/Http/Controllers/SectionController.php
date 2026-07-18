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
        'materials.materialType',
        'materials.unlockRules.unlockType',
        'quizzes',
        'assignments',
    ])->findOrFail($id);

    $course = $section->course;

    $materialTypes = \App\Models\MaterialType::all();
    $unlockRuleTypes = \App\Models\UnlockRuleType::all();

    $roles = [
        'student' => 'Student',
        'teacher' => 'Teacher',
        'admin' => 'Admin',
    ];

    $user = auth()->user();
    $isManager = $user->isAdmin($course->id) || $user->isTeacher($course->id);

    $materials = $section->materials;

    $materialProgress = \App\Models\UserMaterialProgress::where('user_id', $user->id)
        ->whereIn('material_id', $materials->pluck('id'))
        ->get()
        ->keyBy('material_id');

    $visibleMaterials = $materials->filter(function ($material) use ($user, $isManager, $materialProgress) {
        if ($isManager || auth()->id() == $material->created_by) {
            return true;
        }
        if (!$material->visibility) {
            return false;
        }
        return $this->materialIsUnlocked($material, $user, $materialProgress);
    })->values();

    // Quizzes: no created_by column, so visibility is the only gate for
    // non-managers.
    $visibleQuizzes = $section->quizzes
        ->filter(fn ($quiz) => $isManager || $quiz->visibility)
        ->map(function ($quiz) {
            $quiz->activity_kind = 'quiz';
            return $quiz;
        });

    // Assignments: has created_by, so its creator can see their own even
    // while hidden, same rule as materials.
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

    return view('courses.sections.show', compact(
        'section',
        'course',
        'materialTypes',
        'unlockRuleTypes',
        'roles',
        'materials',
        'visibleMaterials',
        'visibleActivities',
        'visibleQuizzes'
    ));
}

    /**
     * Whether all of a material's unlock rules are satisfied for the given
     * user. Rules are combined with AND — every rule attached to the
     * material must pass for it to be visible.
     */
    private function materialIsUnlocked($material, $user, $materialProgress)
    {
        if ($material->unlockRules->isEmpty()) {
            return true;
        }

        foreach ($material->unlockRules as $rule) {
            if (!$this->unlockRulePasses($rule, $user, $materialProgress)) {
                return false;
            }
        }

        return true;
    }

    private function unlockRulePasses($rule, $user, $materialProgress)
    {
        switch ($rule->unlockType->code) {

            case 'completed_activity':
                // target_id = the material that must be completed first.
                $progress = $materialProgress->get($rule->target_id)
                    ?? \App\Models\UserMaterialProgress::where('user_id', $user->id)
                    ->where('material_id', $rule->target_id)
                    ->first();

                return $progress && !is_null($progress->completed_at);

            case 'score':
                // ASSUMPTION: no dedicated "score" column exists in the schema,
                // so this uses progress_percent on the target material as a
                // stand-in for score. Minimum threshold comes from
                // value->min_score (JSON), falling back to val_str.
                $progress = $materialProgress->get($rule->target_id)
                    ?? \App\Models\UserMaterialProgress::where('user_id', $user->id)
                    ->where('material_id', $rule->target_id)
                    ->first();

                if (!$progress) {
                    return false;
                }

                $minScore = data_get($rule->value, 'min_score', $rule->val_str);

                return $minScore !== null && $progress->progress_percent >= (int) $minScore;

            case 'available_at':
                // val_str (or value->available_at) holds a datetime string;
                // material unlocks once now() has passed it.
                $availableAt = $rule->val_str ?: data_get($rule->value, 'available_at');

                if (!$availableAt) {
                    return true;
                }

                return now()->greaterThanOrEqualTo(\Carbon\Carbon::parse($availableAt));

            case 'role':
                // val_str holds the required role code ('admin' | 'teacher' | 'student').
                return match ($rule->val_str) {
                    'admin' => $user->isAdmin(),
                    'teacher' => $user->isTeacher() || $user->isAdmin(),
                    'student' => true,
                    default => true,
                };

            default:
                // Unknown rule type — fail safe by not blocking visibility.
                return true;
        }
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
