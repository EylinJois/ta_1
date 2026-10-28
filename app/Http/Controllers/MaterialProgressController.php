<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\MaterialCompletion;
use App\Models\UserCourseProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MaterialProgressController extends Controller
{
    public function update(Request $request, Material $material)
    {
        $this->authorize('complete', $material);
        $user = $request->user();

        DB::transaction(function () use ($user, $material) {

            MaterialCompletion::firstOrCreate(
                ['user_id' => $user->id, 'material_id' => $material->id],
                ['completed_at' => now()]
            );

            $this->recalculateCourseProgress($user, $material->section->course);
        });

        return response()->json(['status' => 'ok']);
    }

    private function recalculateCourseProgress($user, $course)
    {
        $materialIds = $course->sections->flatMap->materials->pluck('id');

        $totalMaterials = $materialIds->count();
        if ($totalMaterials === 0) {
            return;
        }

        $completedCount = MaterialCompletion::where('user_id', $user->id)
            ->whereIn('material_id', $materialIds)
            ->count();

        $percent = (int) round(($completedCount / $totalMaterials) * 100);

        $courseProgress = UserCourseProgress::firstOrNew([
            'user_id' => $user->id,
            'course_id' => $course->id,
        ]);

        $courseProgress->progress_percent = $percent;
        $courseProgress->last_access = now();

        if ($percent >= 100 && !$courseProgress->completed_at) {
            $courseProgress->completed_at = now();
        }

        $courseProgress->save();
    }
}