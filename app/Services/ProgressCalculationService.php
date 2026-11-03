<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Material;
use App\Models\MaterialCompletion;
use App\Models\User;

class ProgressCalculationService
{
    public function calculate(User $user, Course $course): array
    {
        $visibleMaterialIds = $this->getVisibleMaterialIds($user, $course);

        $totalVisibleMaterials = $visibleMaterialIds->count();
        $completedVisibleMaterials = $totalVisibleMaterials > 0
            ? MaterialCompletion::where('user_id', $user->id)
                ->whereIn('material_id', $visibleMaterialIds)
                ->distinct()
                ->count('material_id')
            : 0;

        $progress = $totalVisibleMaterials > 0
            ? (int) round($completedVisibleMaterials / $totalVisibleMaterials * 100)
            : 0;

        return ['progress' => $progress];
    }

    private function getVisibleMaterialIds(User $user, Course $course)
    {
        $isManager = $user->isManagerInCourse($course->id);

        return Material::query()
            ->whereHas('section', fn ($query) => $query->where('course_id', $course->id))
            ->when(!$isManager, function ($query) use ($user) {
                $query->where(function ($query) use ($user) {
                    $query->where('visibility', true);
                });
            })
            ->pluck('id');
    }
}
