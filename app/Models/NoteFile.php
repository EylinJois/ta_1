<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class NoteFile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'media_asset_id',
        'note_id',
        'original_name'
    ];
}