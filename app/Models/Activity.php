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

    public function activityType() { return $this->belongsTo(ActivityType::class, 'type_id'); }
    public function sourceType() { return $this->belongsTo(SourceType::class, 'source_type_id'); }
    public function course() { return $this->belongsTo(Course::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function participants() { return $this->belongsToMany(User::class, 'calendar_event_participants', 'activity_id', 'user_id'); }
}