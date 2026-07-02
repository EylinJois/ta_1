<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Activity extends Model
{
    protected $fillable = [
        'name',
        'description',
        'status',
        'user_id',
        'starts_at',
        'ends_at',
        'type_id',
        'created_by',
        'source_type_id',
        'source_id',
        'meeting_link',
        'is_all_day',
        'notify_minutes',
        'course_id'
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_all_day' => 'boolean'
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(
            ActivityType::class
        );
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'calendar_event_participants'
        );
    }
}