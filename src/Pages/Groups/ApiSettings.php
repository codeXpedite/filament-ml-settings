<?php

namespace CodeXpedite\FilamentMlSettings\Pages\Groups;

use CodeXpedite\FilamentMlSettings\Pages\BaseGroupSettings;

class ApiSettings extends BaseGroupSettings
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-code-bracket';

    protected static ?int $navigationSort = 8004;

    protected static ?string $slug = 'manage-settings/api';

    protected function getSettingsGroup(): string
    {
        return 'api';
    }

    protected function getGroupLabel(): string
    {
        return 'API';
    }
}
