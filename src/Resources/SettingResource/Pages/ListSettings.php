<?php

namespace CodeXpedite\FilamentMlSettings\Resources\SettingResource\Pages;

use CodeXpedite\FilamentMlSettings\Resources\SettingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSettings extends ListRecords
{
    protected static string $resource = SettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
