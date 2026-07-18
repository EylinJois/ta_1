<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// app/Models/Submission.php
class Submission extends Model
{
    protected $fillable = [
        'user_id', 'assignment_id', 'submitted_text', 'status',
        'score', 'feedback', 'graded_by', 'graded_at', 'submitted_at',
    ];

    protected $casts = [
        'graded_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function assignment() { return $this->belongsTo(Assignment::class); }
    public function grader() { return $this->belongsTo(User::class, 'graded_by'); }

    public function mediaAssets()
{
    return $this->belongsToMany(MediaAsset::class, 'submission_files', 'submission_id', 'media_asset_id')
        ->withTimestamps();
}
}
