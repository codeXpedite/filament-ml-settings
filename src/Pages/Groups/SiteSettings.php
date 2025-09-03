<?php

namespace CodeXpedite\FilamentMlSettings\Pages\Groups;

use CodeXpedite\FilamentMlSettings\Pages\BaseGroupSettings;

class SiteSettings extends BaseGroupSettings
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?int $navigationSort = 8002;

    protected static ?string $slug = 'manage-settings/site';

    protected function getSettingsGroup(): string
    {
        return 'site';
    }

    protected function getGroupLabel(): string
    {
        return 'Site';
    }
}
