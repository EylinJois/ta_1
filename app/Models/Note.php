<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Note extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'content',
        'pinned',
        'folder_id',
        'user_id'
    ];

    protected $casts = [
        'pinned' => 'boolean'
    ];

    public function materials(): BelongsToMany
    {
        return $this->belongsToMany(
            Material::class,
            'note_materials'
        );
    }

    public function notefiles(): HasMany
    {
         return $this->hasMany(NoteFile::class, 'note_id');
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }
}