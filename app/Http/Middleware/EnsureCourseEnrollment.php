<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\CourseUserRole;

class EnsureCourseEnrollment
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {

        $user = auth()->user();

        if (!$user) {
            abort(401);
        }

        if ($user->is_admin) {
            return $next($request);
        }

        $courseId = $request->route('id');

        if (!$courseId) {
            return $next($request);
        }

        $enrolled = CourseUserRole::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->exists();

        if (!$enrolled) {
            abort(403, 'You are not enrolled in this course.');
        }

        return $next($request);
    }
}
