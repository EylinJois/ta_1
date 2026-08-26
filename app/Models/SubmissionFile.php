<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class SubmissionFile extends Pivot
{

    protected $table = 'submission_files';
    public $timestamps = false; // no updated_at column
    protected $fillable = ['submission_id', 'media_asset_id'];

    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;
}