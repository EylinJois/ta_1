<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Material extends Model
{
    protected $fillable = [
        'name',
        'description',
        'material_type_id',
        'created_by',
        'visibility',
        'section_id'
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(
            Section::class
        );
    }

    public function unlockRules(): HasMany
    {
        return $this->hasMany(
            MaterialUnlockRule::class
        );
    }
}