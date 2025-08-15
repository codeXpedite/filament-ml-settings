<?php

use CodeXpedite\FilamentMlSettings\Services\SettingsManager;

if (!function_exists('settings')) {
    function settings($key = null, $default = null)
    {
        $manager = app('settings');
        
        if ($key === null) {
            return $manager;
        }
        
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $manager->set($k, $v);
            }
            return true;
        }
        
        return $manager->get($key, $default);
    }
}