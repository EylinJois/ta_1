<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserCertification extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'certification_type_id',
        'certificate_num',
        'score',
        'expired_at',
        'verified_at',
        'verified_by',
        'status'
    ];

    protected $casts = [
        'expired_at' => 'date',
        'verified_at' => 'datetime'
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function certificationType() { return $this->belongsTo(CertificationType::class); }
    public function verifier() { return $this->belongsTo(User::class, 'verified_by'); }
    public function files() { return $this->hasMany(UserCertificationFile::class); }
}