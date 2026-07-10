<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    public function materialType(): BelongsTo
    {
        return $this->belongsTo(
            MaterialType::class
        );
    }

    public function materialLink(): HasOne
    {
        return $this->hasOne(
            MaterialLink::class
        );
    }

    public function materialYoutube(): HasOne
    {
        return $this->hasOne(
            MaterialYoutube::class
        );
    }

    public function materialPage(): HasOne
    {
        return $this->hasOne(
            MaterialPage::class
        );
    }

    public function materialAsset(): HasOne
    {
        return $this->hasOne(
            MaterialAsset::class
        );
    }


}