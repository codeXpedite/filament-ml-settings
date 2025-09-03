<?php

namespace CodeXpedite\FilamentMlSettings\Pages;

/**
 * Dynamic settings page that can be instantiated for any group
 * This allows for creating settings pages on-the-fly without pre-defining classes
 */
class DynamicGroupSettings extends BaseGroupSettings
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog';

    protected string $group;

    protected string $label;

    protected string $icon;

    protected int $sort;

    public static function make(string $group, ?string $label = null, ?string $icon = null, int $sort = 99): static
    {
        $instance = new static;
        $instance->group = $group;
        $instance->label = $label ?? ucfirst(str_replace(['_', '-'], ' ', $group));
        $instance->icon = $icon ?? 'heroicon-o-cog';
        $instance->sort = $sort;

        // Set static properties for navigation
        static::$navigationIcon = $instance->icon;
        static::$navigationSort = $instance->sort;
        static::$slug = 'manage-settings/'.$group;

        return $instance;
    }

    protected function getSettingsGroup(): string
    {
        return $this->group;
    }

    protected function getGroupLabel(): string
    {
        return $this->label;
    }

    public static function getSlug(): string
    {
        return static::$slug ?? 'manage-settings/dynamic';
    }
}
