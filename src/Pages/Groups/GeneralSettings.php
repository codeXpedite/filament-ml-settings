<?php

namespace CodeXpedite\FilamentMlSettings\Pages\Groups;

use CodeXpedite\FilamentMlSettings\Pages\BaseGroupSettings;

class GeneralSettings extends BaseGroupSettings
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-home';
    
    protected static ?int $navigationSort = 8001;
    
    protected static ?string $slug = 'manage-settings/general';
    
    protected function getSettingsGroup(): string
    {
        return 'general';
    }
    
    protected function getGroupLabel(): string
    {
        return 'General';
    }
}