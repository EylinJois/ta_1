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
        MaterialCompletion::firstOrCreate(
            ['user_id' => $user->id, 'material_id' => $material->id],
            ['completed_at' => now()]
        );

        return response()->json(['status' => 'ok']);
    }
}
