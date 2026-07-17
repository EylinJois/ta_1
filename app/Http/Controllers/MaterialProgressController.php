<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Material;

class MaterialProgressController extends Controller
{
    public function update(Request $request, Material $material)
    {
        $data = $request->validate([
            'progress_percent' => 'required_without:force_complete|integer|min:0|max:100',
            'duration_delta'   => 'nullable|integer|min:0', // seconds since last heartbeat
            'force_complete'   => 'nullable|boolean',       // manual "mark complete" button
        ]);

        $user = $request->user();

        $progress = \App\Models\UserMaterialProgress::firstOrNew([
            'user_id' => $user->id,
            'material_id' => $material->id,
        ]);

        $incomingPercent = $data['force_complete'] ?? false
            ? 100
            : (int) ($data['progress_percent'] ?? 0);

        // Never let auto-tracking regress a value that's already higher
        // (e.g. a stray heartbeat firing after manual completion).
        $progress->progress_percent = max($progress->progress_percent ?? 0, $incomingPercent);

        $progress->study_duration_seconds =
            ($progress->study_duration_seconds ?? 0) + ($data['duration_delta'] ?? 0);

        $progress->completion_count = $progress->completion_count ?? 0;

        if ($progress->progress_percent >= 100 && !$progress->completed_at) {
            $progress->completed_at = now();
            $progress->completion_count += 1;
        }

        $progress->save();

        $this->recalculateCourseProgress($user, $material->section->course);

        return response()->json(['progress' => $progress]);
    }

    private function recalculateCourseProgress($user, $course)
    {
        $materialIds = $course->sections->flatMap->materials->pluck('id');

        $totalMaterials = $materialIds->count();
        if ($totalMaterials === 0) return;

        $completedCount = \App\Models\UserMaterialProgress::where('user_id', $user->id)
            ->whereIn('material_id', $materialIds)
            ->whereNotNull('completed_at')
            ->count();

        $percent = (int) round(($completedCount / $totalMaterials) * 100);

        $courseProgress = \App\Models\UserCourseProgress::firstOrNew([
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