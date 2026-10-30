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
    ];

    protected $casts = [
        'media_asset_id' => 'integer',
        'note_id' => 'integer'
    ];

    public function mediaAsset()
    {
        return $this->belongsTo(MediaAsset::class, 'media_asset_id');
    }


    public function note()
    {
        return $this->belongsTo(Note::class, 'note_id');
    }
}