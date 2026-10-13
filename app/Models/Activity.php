<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Activity extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'name',
        'description',
        'starts_at',
        'ends_at',
        'type',
        'source_type',
        'source_id',
        'is_all_day',
        'meeting_link',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_all_day' => 'boolean'
    ];

    public const TYPE_ICONS = [
        'Meeting'       => 'bi-camera-video-fill',
        'Reminder'      => 'bi-bell-fill',
        'Quiz'          => 'bi-patch-question-fill',
        'Assignment'    => 'bi-file-earmark-text-fill',
        'Study Session' => 'bi-book-fill',
    ];

    public const DEFAULT_ICON = 'bi-calendar-event-fill';
    public const SOURCE_ROUTES = [
        'quiz'       => ['route' => 'quizzes.show',     'label' => 'Quiz'],
        'assignment' => ['route' => 'assignments.show', 'label' => 'Assignment'],
    ];

    public function getSourceUrlAttribute(): ?string
    {
        $source = self::SOURCE_ROUTES[Str::lower($this->source_type ?? '')] ?? null;

        if ($this->source_id === null || $source === null) {
            return null;
        }

        return route($source['route'], $this->source_id);
    }

    public function getSourceLabelAttribute(): ?string
    {
        return self::SOURCE_ROUTES[Str::lower($this->source_type ?? '')]['label'] ?? null;
    }

    public function participants()
    {
        return $this->belongsToMany(User::class, 'calendar_event_participants', 'activity_id', 'user_id')
            ->withPivot('status', 'deleted_at')
            ->withTimestamps();
    }
    public function source()
    {
        if ($this->source_type == 'assignment') {
            return $this->belongsTo(Assignment::class, 'source_id');
        } else {
            return $this->belongsTo(Quiz::class, 'source_id');
        }
    }

    public function getIconAttribute(): string
    {
        return self::TYPE_ICONS[$this->type] ?? self::DEFAULT_ICON;
    }

    public function getDisplayStatusAttribute(): string
    {
        if ($this->pivot?->deleted_at) {
            return 'cancelled';
        }

        return $this->pivot?->status ?? 'scheduled';
    }
}
