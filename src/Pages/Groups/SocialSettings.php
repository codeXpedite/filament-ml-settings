<?php

namespace CodeXpedite\FilamentMlSettings\Pages\Groups;

use CodeXpedite\FilamentMlSettings\Pages\BaseGroupSettings;

class SocialSettings extends BaseGroupSettings
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-share';

    protected static ?int $navigationSort = 8006;

    protected static ?string $slug = 'manage-settings/social';

    protected function getSettingsGroup(): string
    {
        return 'social';
    }

    protected function getGroupLabel(): string
    {
        return 'Social';
    }
}
