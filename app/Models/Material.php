<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Material extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'name',
        'description',
        'type',
        'visibility',
        'section_id'
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(
            Section::class
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


    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }
}