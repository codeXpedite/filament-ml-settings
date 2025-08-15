<?php

namespace CodeXpedite\FilamentMlSettings\Resources\SettingResource\Pages;

use CodeXpedite\FilamentMlSettings\Resources\SettingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSetting extends CreateRecord
{
    protected static string $resource = SettingResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}