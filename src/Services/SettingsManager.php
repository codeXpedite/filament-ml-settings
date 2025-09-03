<?php

namespace CodeXpedite\FilamentMlSettings\Services;

use CodeXpedite\FilamentMlSettings\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingsManager
{
    protected $cache = [];

    protected $cacheEnabled = true;

    protected $cacheTime = 3600;

    public function get($key, $default = null, $locale = null)
    {
        if ($this->cacheEnabled && isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $value = Setting::getSetting($key, $default, $locale);

        if ($this->cacheEnabled) {
            $this->cache[$key] = $value;
        }

        return $value;
    }

    public function set($key, $value, $locale = null)
    {
        $result = Setting::setSetting($key, $value, $locale);

        if ($result && $this->cacheEnabled) {
            unset($this->cache[$key]);
            Cache::forget("settings.{$key}");
        }

        return $result;
    }

    public function has($key)
    {
        return Setting::where('key', $key)->exists();
    }

    public function forget($key)
    {
        unset($this->cache[$key]);
        Cache::forget("settings.{$key}");

        return Setting::where('key', $key)->delete();
    }

    public function all($group = null, $locale = null)
    {
        $query = Setting::with('translations');

        if ($group) {
            $query->where('group', $group);
        }

        $settings = $query->get();

        $result = [];
        foreach ($settings as $setting) {
            if ($locale && $setting->is_translatable) {
                $result[$setting->key] = $setting->translate($locale)?->value ?? $setting->default_value;
            } else {
                $result[$setting->key] = $setting->value;
            }
        }

        return $result;
    }

    public function group($group, $locale = null)
    {
        return $this->all($group, $locale);
    }

    public function create(array $data)
    {
        return Setting::create($data);
    }

    public function update($key, array $data)
    {
        $setting = Setting::where('key', $key)->first();

        if (! $setting) {
            return false;
        }

        $setting->update($data);

        if ($this->cacheEnabled) {
            unset($this->cache[$key]);
            Cache::forget("settings.{$key}");
        }

        return $setting;
    }

    public function getGroupedSettings()
    {
        return Setting::getGroupedSettings();
    }

    public function clearCache()
    {
        $this->cache = [];
        Cache::flush();
    }

    public function disableCache()
    {
        $this->cacheEnabled = false;

        return $this;
    }

    public function enableCache()
    {
        $this->cacheEnabled = true;

        return $this;
    }

    public function setCacheTime($seconds)
    {
        $this->cacheTime = $seconds;

        return $this;
    }
}
