<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Clas;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\ClassUserRole;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $anchor = $request->filled('date')
            ? Carbon::parse($request->input('date'))
            : now();

        $startOfWeek = $anchor->copy()->startOfWeek(Carbon::SUNDAY);
        $endOfWeek = $anchor->copy()->endOfWeek(Carbon::SATURDAY);


        $activities = $user->activities()
            ->whereBetween('activities.starts_at', [$startOfWeek, $endOfWeek])
            ->orderBy('activities.starts_at')
            ->get()
            ->groupBy(fn($a) => $a->starts_at->format('Y-m-d'));

        $days = collect();
        $cursor = $startOfWeek->copy();
        while ($cursor->lte($endOfWeek)) {
            $days->push($cursor->copy());
            $cursor->addDay();
        }

        $managedClasses = $this->classesAvailableForEvent($user);

        return view('calendar.index', compact(
            'days',
            'activities',
            'startOfWeek',
            'endOfWeek',
            'managedClasses',
        ));
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'event_date' => 'required|date',
            'starts_time' => 'required|date_format:H:i',
            'ends_time' => 'nullable|date_format:H:i|after:starts_time',
            'type' => 'required|string|max:255',
            'is_all_day' => 'required|boolean',
            'meeting_link' => 'nullable|url|max:2048',
            'class_ids' => 'nullable|array',
            'class_ids.*' => 'integer|exists:classes,id',
        ]);

        $classIds = collect($validated['class_ids'] ?? [])->unique();

        $availableClassIds = $this->classesAvailableForEvent(auth()->user())->pluck('id');
        abort_if($classIds->diff($availableClassIds)->isNotEmpty(), 403);

        $startsAt = Carbon::parse($validated['event_date'] . ' ' . $validated['starts_time']);
        $endsAt = $validated['ends_time']
            ? Carbon::parse($validated['event_date'] . ' ' . $validated['ends_time'])
            : null;

        $activity = Activity::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'type' => $validated['type'],
            'source_type' => null,
            'source_id' => null,
            'is_all_day' => $validated['is_all_day'],
            'meeting_link' => $validated['meeting_link'] ?? null,
        ]);

        $activity->participants()->attach(auth()->id(), ['status' => 'scheduled']);

        if ($classIds->isNotEmpty()) {
            // every member of the chosen classes, whatever their role
            $memberIds = ClassUserRole::whereIn('class_id', $classIds)
                ->pluck('user_id')
                ->unique();

            $activity->participants()->syncWithoutDetaching($memberIds);
        }

        return redirect()
            ->route('calendar.index', ['date' => $startsAt->format('Y-m-d')])
            ->with('success', 'Event added to the calendar.');
    }

    public function addParticipants(Request $request, Activity $activity)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $activity->participants()->syncWithoutDetaching($validated['user_id']);

        return back()->with('success', 'Participant added to the event.');
    }

    public function show(Activity $activity)
    {
        $participation = $this->myParticipation($activity);
        $isCancelled = $participation->pivot->deleted_at !== null;

        $activity->setAttribute('my_status', $isCancelled ? 'cancelled' : $participation->pivot->status);
        $activity->setAttribute('can_cancel', ! $isCancelled && $this->canCancel($activity));
        $activity->append(['source_url', 'source_label']);

        return response()->json($activity);
    }

    public function updateStatus(Request $request, Activity $activity)
    {
        $participation = $this->myParticipation($activity);

        abort_if($participation->pivot->deleted_at !== null, 422, 'This event was cancelled.');

        $validated = $request->validate([
            'status' => 'required|in:scheduled,completed,missed',
        ]);

        $activity->participants()->updateExistingPivot(auth()->id(), [
            'status' => $validated['status']
        ]);

        return response()->json(['status' => 'ok', 'new_status' => $validated['status']]);
    }

    public function cancel(Activity $activity)
    {
        abort_unless($this->canCancel($activity), 403);

        // soft-delete every participant's row = the event is cancelled for everyone
        $activity->participants()->updateExistingPivot(
            $activity->participants()->pluck('users.id'),
            ['deleted_at' => now()]
        );

        return response()->json(['status' => 'ok', 'new_status' => 'cancelled']);
    }

    private function canCancel(Activity $activity): bool
    {
        $user = auth()->user();

        $otherParticipantIds = $activity->participants()
            ->where('users.id', '!=', $user->id)
            ->pluck('users.id');

        return ClassUserRole::whereIn('class_id', $user->managedClasses()->pluck('classes.id'))
            ->whereIn('user_id', $otherParticipantIds)
            ->exists();
    }

    private function classesAvailableForEvent($user)
    {
        $classes = $user->managedClasses()
            ->whereHas('course')
            ->with('course')
            ->get();

        $adminCourseIds = $classes
            ->filter(fn($class) => $class->pivot->role === 'admin')
            ->pluck('course_id')
            ->unique();

        if ($adminCourseIds->isNotEmpty()) {
            $classes = $classes->merge(
                Clas::whereIn('course_id', $adminCourseIds)
                    ->whereHas('course')
                    ->with('course')
                    ->get()
            );
        }

        return $classes
            ->unique('id')
            ->sortBy(fn($class) => $class->course->name)
            ->values();
    }

    public function update(Request $request, Activity $activity)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'event_date' => 'required|date',
            'starts_time' => 'required|date_format:H:i',
            'ends_time' => 'nullable|date_format:H:i|after:starts_time',
            'type' => 'required|string|max:255',
            'is_all_day' => 'required|boolean',
            'meeting_link' => 'nullable|url|max:2048',
        ]);

        $startsAt = Carbon::parse($validated['event_date'] . ' ' . $validated['starts_time']);
        $endsAt = $validated['ends_time']
            ? Carbon::parse($validated['event_date'] . ' ' . $validated['ends_time'])
            : null;

        $activity->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
            'is_all_day' => $validated['is_all_day'],
            'meeting_link' => $validated['meeting_link'] ?? null,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ]);

        return back()->with('success', 'Event updated.');
    }

    public function destroy(Activity $activity)
    {
        $activity->delete();

        return back()->with('success', 'Event removed.');
    }


    private function myParticipation(Activity $activity)
    {
        return $activity->participants()->whereKey(auth()->id())->firstOrFail();
    }
}
