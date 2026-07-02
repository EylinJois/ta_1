<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Folder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'user_id',
        'parent_folder_id'
    ];

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(
            Folder::class,
            'parent_folder_id'
        );
    }
}
