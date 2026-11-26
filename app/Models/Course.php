<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'img',
        'description'
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function sections(): HasMany
{
    return $this->hasMany(Section::class)->orderBy('order_idx', 'asc');
}

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function progressReports(): HasMany
    {
        return $this->hasMany(ProgressReport::class);
    }

    public function classes(): HasMany
    {
        return $this->hasMany(Clas::class);
    }

    public function imgMedia()
    {
        return $this->belongsTo(MediaAsset::class, 'img_media_id');
    }
}
