<?php

namespace App\Http\Controllers;

use App\Models\Attempt;
use App\Models\Activity;
use App\Models\ClassUserRole;
use App\Models\ProgressReport;
use App\Models\Quiz;

class HomeController extends Controller
{
    private const DONE = ['submitted', 'graded'];

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = auth()->user();

        // every class the user is in (pivot->role says which role)
        $memberships = $user->classes()
            ->with('course')
            ->orderBy('name')
            ->get()
            ->filter(fn ($class) => $class->course)
            ->values();

        $roleOf = fn ($class) => strtolower($class->pivot->role);

        $managedClasses = $memberships->filter(fn ($c) => in_array($roleOf($c), ['admin', 'teacher']))->values();
        $adminClasses   = $memberships->filter(fn ($c) => $roleOf($c) === 'admin')->values();
        $studentClasses = $memberships->filter(fn ($c) => $roleOf($c) === 'student')->values();

        // e.g. ['admin' => 1, 'student' => 2]
        $roleSummary = $memberships->groupBy($roleOf)->map->count();

        return view('home', [
            'memberships'    => $memberships,
            'managedClasses' => $managedClasses,
            'studentClasses' => $studentClasses,
            'roleSummary'    => $roleSummary,
            'schedule'       => $this->schedule($memberships, $managedClasses),
            'learner'        => $studentClasses->isNotEmpty() ? $this->learnerData($user, $studentClasses) : null,
            'manager'        => $managedClasses->isNotEmpty() ? $this->managerData($managedClasses, $adminClasses) : null,
        ]);
    }


    private function schedule($memberships, $managedClasses)
    {
        $events = auth()->user()->activities()->whereBetween('activities.starts_at', [now(), now()->addDays(14)])
    ->orderBy('activities.starts_at')->get();

        return $events;
    }

    private function learnerData($user, $studentClasses): array
    {
        $courseIds = $studentClasses->pluck('course_id')->unique();
        $now       = now();

        $takenQuizIds = Attempt::where('user_id', $user->id)
            ->whereIn('status', self::DONE)
            ->select('quiz_id');

        $dueSoon = Quiz::where('visibility', true)
            ->whereHas('section', fn ($q) => $q->whereIn('course_id', $courseIds))
            ->whereBetween('due_at', [$now, now()->addDays(7)])
            ->whereNotIn('id', $takenQuizIds)
            ->count();

        return [
            'dueSoon'        => $dueSoon,
            'avgScore'       => Attempt::where('user_id', $user->id)->whereIn('status', self::DONE)->avg('score'),
            'latestProgress' => ProgressReport::where('user_id', $user->id)->latest()->value('progress'),
            'inProgress'     => Attempt::with('quiz.section.course')
                ->where('user_id', $user->id)
                ->where('status', 'in_progress')
                ->latest('started_at')
                ->get(),
            'recentResults'  => Attempt::with('quiz')
                ->where('user_id', $user->id)
                ->whereIn('status', self::DONE)
                ->latest('updated_at')
                ->take(5)
                ->get(),
        ];
    }

    private function managerData($managedClasses, $adminClasses): array
    {
        $classIds  = $managedClasses->pluck('id');
        $courseIds = $managedClasses->pluck('course_id')->unique();
        $now       = now();

        // students in the classes this user manages
        $studentRows = ClassUserRole::whereIn('class_id', $classIds)->where('role', 'student');
        $studentIds  = (clone $studentRows)->select('user_id');

        $quizzesInScope = Quiz::whereHas('section', fn ($q) => $q->whereIn('course_id', $courseIds));
        $scopedQuizIds  = (clone $quizzesInScope)->select('id');

        $openQuizzes = (clone $quizzesInScope)
            ->where('visibility', true)
            ->where(fn ($q) => $q->whereNull('opened_at')->orWhere('opened_at', '<=', $now))
            ->where(fn ($q) => $q->whereNull('due_at')->orWhere('due_at', '>=', $now))
            ->count();

        $pendingGrading = Attempt::where('status', 'submitted')
            ->whereIn('quiz_id', $scopedQuizIds)
            ->whereIn('user_id', $studentIds)
            ->count();

        $closingSoon = (clone $quizzesInScope)
            ->with('section.course')
            ->whereBetween('due_at', [$now, now()->addDays(3)])
            ->orderBy('due_at')
            ->take(3)
            ->get();

        // latest finished attempts by students in managed classes
        $attempts = Attempt::with(['user', 'quiz.section'])
            ->whereIn('quiz_id', $scopedQuizIds)
            ->whereIn('user_id', $studentIds)
            ->whereIn('status', self::DONE)
            ->latest('updated_at')
            ->take(8)
            ->get();

        // class name(s) of each student, limited to the quiz's course
        $memberRows = ClassUserRole::whereIn('class_id', $classIds)
            ->where('role', 'student')
            ->whereIn('user_id', $attempts->pluck('user_id'))
            ->get()
            ->groupBy('user_id');

        $classById = $managedClasses->keyBy('id');

        $activity = $attempts->map(function ($attempt) use ($memberRows, $classById) {
            $courseId = $attempt->quiz->section->course_id;

            $classNames = ($memberRows[$attempt->user_id] ?? collect())
                ->map(fn ($row) => $classById[$row->class_id] ?? null)
                ->filter(fn ($class) => $class && $class->course_id == $courseId)
                ->pluck('name')
                ->implode(', ');

            return ['attempt' => $attempt, 'classes' => $classNames ?: '—'];
        });

        return [
            'classCount'     => $managedClasses->count(),
            'studentCount'   => (clone $studentRows)->distinct()->count('user_id'),
            'openQuizzes'    => $openQuizzes,
            'pendingGrading' => $pendingGrading,
            'closingSoon'    => $closingSoon,
            'activity'       => $activity,
            'adminCourses'   => $adminClasses->pluck('course')->unique('id')->values(),
        ];
    }
}