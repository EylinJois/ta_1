<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
}