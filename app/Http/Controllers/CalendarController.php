<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

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

        $activities = Activity::where(function ($q) use ($user) {
                $q->whereHas('participants', fn($p) => $p->where('user_id', $user->id));
            })
            ->whereBetween('starts_at', [$startOfWeek, $endOfWeek])
            ->orderBy('starts_at')
            ->get()
            ->groupBy(fn($a) => $a->starts_at->format('Y-m-d'));


        $days = collect();
        $cursor = $startOfWeek->copy();
        while ($cursor->lte($endOfWeek)) {
            $days->push($cursor->copy());
            $cursor->addDay();
        }

        return view('calendar.index', compact(
            'days',
            'activities',
            'startOfWeek',
            'endOfWeek',
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
        ]);

        $startsAt = Carbon::parse($validated['event_date'] . ' ' . $validated['starts_time']);
        $endsAt = $validated['ends_time']
            ? Carbon::parse($validated['event_date'] . ' ' . $validated['ends_time'])
            : null;

        $activity = Activity::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'status' => 'scheduled',
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'type' => $validated['type'],
            'source_type' => null,
            'source_id' => null,
            'is_all_day' => $validated['is_all_day'],
            'meeting_link' => $validated['meeting_link'] ?? null,
        ]);

        $activity->participants()->attach(auth()->id());
        return back()->with('success', 'Event added to your calendar.');
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
        abort_unless(
            $activity->participants()->where('user_id', auth()->id())->exists(),
            403
        );

        return response()->json($activity);
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
            'status' => 'required|in:scheduled,completed,cancelled,missed',
        ]);

        $startsAt = Carbon::parse($validated['event_date'] . ' ' . $validated['starts_time']);
        $endsAt = $validated['ends_time']
            ? Carbon::parse($validated['event_date'] . ' ' . $validated['ends_time'])
            : null;

        $activity->update([
            ...$validated,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ]);

        return back()->with('success', 'Event updated.');
    }

    public function updateStatus(Request $request, Activity $activity)
    {
        $validated = $request->validate([
            'status' => 'required|in:completed,cancelled,scheduled',
        ]);

        $activity->update(['status' => $validated['status']]);

        return response()->json(['status' => 'ok', 'new_status' => $activity->status]);
    }

    public function destroy(Activity $activity)
    {
        $activity->delete();

        return back()->with('success', 'Event removed.');
    }
}
