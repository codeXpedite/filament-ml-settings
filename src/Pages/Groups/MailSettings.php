<?php

namespace CodeXpedite\FilamentMlSettings\Pages\Groups;

use CodeXpedite\FilamentMlSettings\Pages\BaseGroupSettings;

class MailSettings extends BaseGroupSettings
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-envelope';
    
    protected static ?int $navigationSort = 8003;
    
    protected static ?string $slug = 'manage-settings/mail';
    
    protected function getSettingsGroup(): string
    {
        return 'mail';
    }
    
    protected function getGroupLabel(): string
    {
        return 'Mail';
    }
}