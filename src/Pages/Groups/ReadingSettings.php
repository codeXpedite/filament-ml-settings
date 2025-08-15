<?php

namespace CodeXpedite\FilamentMlSettings\Pages\Groups;

use CodeXpedite\FilamentMlSettings\Pages\BaseGroupSettings;

class ReadingSettings extends BaseGroupSettings
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-book-open';
    
    protected static ?int $navigationSort = 8005;
    
    protected static ?string $slug = 'manage-settings/reading';
    
    protected function getSettingsGroup(): string
    {
        return 'reading';
    }
    
    protected function getGroupLabel(): string
    {
        return 'Reading';
    }
}