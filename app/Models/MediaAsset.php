<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class MediaAsset extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'storage_disk',
        'file_path',
        'original_name',
        'mime',
        'size',
        'uploaded_by',
        'created_at',
        'updated_at',
    ];
}
