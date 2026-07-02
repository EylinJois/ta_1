<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialUnlockRule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'material_id',
        'unlock_type_id',
        'target_id',
        'value'
    ];

    protected $casts = [
        'value' => 'array'
    ];

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function unlockType(): BelongsTo
    {
        return $this->belongsTo(UnlockRuleType::class, 'unlock_type_id');
    }
}