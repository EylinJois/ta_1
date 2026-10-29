<?php

namespace App\Policies;

use App\Models\Material;
use App\Models\Section;
use App\Models\User;

class MaterialPolicy
{
    public function view(User $user, Material $material): bool
    {
        $courseId = $material->section->course_id;

        return $user->isEnrolledInCourse($courseId)
            && (
                $material->visibility
                || $user->isManagerInCourse($courseId)
            );
    }

    public function create(User $user, Section $section): bool
    {
        return $user->isManagerInCourse($section->course_id);
    }

    public function update(User $user, Material $material, ?int $sectionId = null): bool
    {
        if (!$user->isManagerInCourse($material->section->course_id)) {
            return false;
        }

        return $sectionId === null
            || $user->isManagerInCourse(Section::findOrFail($sectionId)->course_id);
    }

    public function delete(User $user, Material $material): bool
    {
        return $user->isManagerInCourse($material->section->course_id);
    }

    public function download(User $user, Material $material): bool
    {
        return $this->view($user, $material);
    }

    public function complete(User $user, Material $material): bool
    {
        return $this->view($user, $material);
    }
}
