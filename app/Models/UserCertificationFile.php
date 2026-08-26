<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

// app/Models/UserCertificationFile.php
class UserCertificationFile extends Model
{
    use SoftDeletes;
    protected $table = 'user_certification_files';
    protected $fillable = ['user_certification_id', 'media_asset_id'];
    public function mediaAsset() { return $this->belongsTo(MediaAsset::class); }
}