<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Overtrue\Pinyin\Pinyin;

class Sentence extends Model
{
    use SoftDeletes;

    protected $fillable = ['material_page_id', 'hanzi', 'pinyin', 'meaning', 'order_idx'];

    protected static function booted()
    {
        static::saving(function (Sentence $sentence) {
            $sentence->pinyin = Pinyin::sentence($sentence->hanzi)->join(' ');
        });
    }

    public function materialPage() { return $this->belongsTo(MaterialPage::class,'material_page_id','material_id'); }
}