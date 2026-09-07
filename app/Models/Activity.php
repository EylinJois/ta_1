<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Activity extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'name',
        'description',
        'status',
        'starts_at',
        'ends_at',
        'type',
        'source_type',
        'source_id',
        'is_all_day',
        'notify_minutes',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_all_day' => 'boolean'
    ];

    public function participants() { return $this->belongsToMany(User::class, 'calendar_event_participants', 'activity_id', 'user_id'); }
}