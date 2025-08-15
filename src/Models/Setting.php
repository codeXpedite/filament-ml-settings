<?php

namespace CodeXpedite\FilamentMlSettings\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model implements TranslatableContract
{
    use Translatable;

    protected $fillable = [
        'key',
        'group',
        'tab',
        'type',
        'name',
        'description',
        'options',
        'rules',
        'default_value',
        'value',
        'is_translatable',
        'order',
    ];

    protected $casts = [
        'options' => 'array',
        'rules' => 'array',
        'is_translatable' => 'boolean',
    ];

    public $translatedAttributes = ['value'];

    protected static function boot()
    {
        parent::boot();

        static::saved(function ($model) {
            Cache::forget('settings');
            Cache::forget("settings.{$model->key}");
        });

        static::deleted(function ($model) {
            Cache::forget('settings');
            Cache::forget("settings.{$model->key}");
        });
    }

    public function getValueAttribute($value)
    {
        if (!$this->is_translatable) {
            return $this->castValue($this->attributes['value'] ?? $this->default_value);
        }

        $translatedValue = $this->getTranslation()?->value;
        return $this->castValue($translatedValue ?? $this->default_value);
    }

    public function setValueAttribute($value)
    {
        if (!$this->is_translatable) {
            $this->attributes['value'] = $this->prepareValueForStorage($value);
        }
    }

    public function setValue($value, $locale = null)
    {
        if (!$this->is_translatable) {
            $this->value = $value;
            $this->save();
            return;
        }

        if ($locale) {
            $this->translateOrNew($locale)->value = $this->prepareValueForStorage($value);
        } else {
            $this->value = $this->prepareValueForStorage($value);
        }
        
        $this->save();
    }

    protected function castValue($value)
    {
        if ($value === null) {
            return null;
        }

        return match ($this->type) {
            'boolean', 'toggle' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'number', 'integer' => (int) $value,
            'float', 'decimal' => (float) $value,
            'json', 'array' => is_string($value) ? json_decode($value, true) : $value,
            'multiselect' => is_string($value) ? json_decode($value, true) : $value,
            default => $value,
        };
    }

    protected function prepareValueForStorage($value)
    {
        if ($value === null) {
            return null;
        }

        return match ($this->type) {
            'boolean', 'toggle' => $value ? '1' : '0',
            'json', 'array', 'multiselect' => is_array($value) ? json_encode($value) : $value,
            default => (string) $value,
        };
    }

    public function scopeByGroup($query, $group)
    {
        return $query->where('group', $group);
    }

    public function scopeByTab($query, $tab)
    {
        return $query->where('tab', $tab);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('id');
    }

    public function getFormattedNameAttribute()
    {
        return $this->name ?: ucfirst(str_replace(['_', '-', '.'], ' ', $this->key));
    }

    public function getValidationRulesAttribute()
    {
        if (!$this->rules) {
            return [];
        }

        return is_array($this->rules) ? $this->rules : explode('|', $this->rules);
    }

    public static function getGroupedSettings()
    {
        return static::with('translations')
            ->ordered()
            ->get()
            ->groupBy('group')
            ->map(function ($group) {
                return $group->groupBy('tab');
            });
    }

    public static function getSetting($key, $default = null, $locale = null)
    {
        $setting = Cache::remember("settings.{$key}", 3600, function () use ($key) {
            return static::where('key', $key)->first();
        });

        if (!$setting) {
            return $default;
        }

        if ($locale && $setting->is_translatable) {
            return $setting->translate($locale)?->value ?? $default;
        }

        return $setting->value ?? $default;
    }

    public static function setSetting($key, $value, $locale = null)
    {
        $setting = static::where('key', $key)->first();
        
        if (!$setting) {
            return false;
        }

        $setting->setValue($value, $locale);
        
        return true;
    }
}