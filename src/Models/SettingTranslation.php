<?php

namespace CodeXpedite\FilamentMlSettings\Models;

use Illuminate\Database\Eloquent\Model;

class SettingTranslation extends Model
{
    protected $fillable = [
        'value',
        'locale',
    ];

    public $timestamps = true;

    public function setting()
    {
        return $this->belongsTo(Setting::class);
    }
}