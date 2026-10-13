<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Assignment extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable=['name','description','visibility','submission_type','due_at',
    'opened_at','accepted_mime','max_size','edit_submission',
    'created_by','section_id','allow_late_submission'];

    protected $casts = [
    'accepted_mime' => 'array',
    'due_at' => 'datetime',
    'opened_at' => 'datetime',
    'edit_submission' => 'boolean',
    'allow_late_submission' => 'boolean',
    'visibility' => 'boolean',
];
    public function section() { return $this->belongsTo(Section::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function submissions() { return $this->hasMany(Submission::class); }

}
