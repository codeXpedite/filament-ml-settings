<?php

namespace CodeXpedite\FilamentMlSettings\Resources;

use CodeXpedite\FilamentMlSettings\Models\Setting;
use CodeXpedite\FilamentMlSettings\Resources\SettingResource\Pages;
use CodeXpedite\FilamentMlSettings\Services\SeederGenerator;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class SettingResource extends Resource
{
    protected static ?string $model = Setting::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static string|\UnitEnum|null $navigationGroup = 'Development';

    protected static ?string $navigationLabel = 'Settings Manager';

    protected static ?int $navigationSort = 9001;

    public static function getNavigationBadge(): ?string
    {
        return app()->environment(['local', 'development']) ? 'DEV' : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Components\Section::make('Setting Configuration')
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('key')
                                    ->label('Setting Key')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('mail.smtp.host')
                                    ->helperText('Unique identifier for this setting'),

                                Forms\Components\TextInput::make('name')
                                    ->label('Display Name')
                                    ->required()
                                    ->placeholder('SMTP Host'),

                                Forms\Components\Select::make('group')
                                    ->label('Group')
                                    ->required()
                                    ->default('general')
                                    ->options([
                                        'general' => 'General',
                                        'mail' => 'Mail Settings',
                                        'site' => 'Site Settings',
                                        'social' => 'Social Media',
                                        'seo' => 'SEO',
                                        'api' => 'API Settings',
                                        'cache' => 'Cache Settings',
                                        'security' => 'Security',
                                    ])
                                    ->searchable(),

                                Forms\Components\TextInput::make('tab')
                                    ->label('Tab')
                                    ->placeholder('SMTP Configuration')
                                    ->helperText('Optional tab within the group'),

                                Forms\Components\Select::make('type')
                                    ->label('Field Type')
                                    ->required()
                                    ->default('text')
                                    ->options([
                                        'text' => 'Text Input',
                                        'textarea' => 'Textarea',
                                        'number' => 'Number',
                                        'boolean' => 'Toggle (Yes/No)',
                                        'select' => 'Select Dropdown',
                                        'multiselect' => 'Multi Select',
                                        'color' => 'Color Picker',
                                        'date' => 'Date Picker',
                                        'datetime' => 'Date Time Picker',
                                        'json' => 'Key-Value (JSON)',
                                        'richtext' => 'Rich Text Editor',
                                    ])
                                    ->reactive()
                                    ->afterStateUpdated(fn ($state, Set $set) => $state === 'boolean' ? $set('default_value', '0') : null
                                    ),

                                Forms\Components\Toggle::make('is_translatable')
                                    ->label('Translatable')
                                    ->default(false)
                                    ->helperText('Allow different values for different languages'),
                            ]),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(2)
                            ->placeholder('Enter a helpful description for this setting'),

                        Forms\Components\KeyValue::make('options')
                            ->label('Options')
                            ->keyLabel('Value')
                            ->valueLabel('Label')
                            ->visible(fn (Get $get) => in_array($get('type'), ['select', 'multiselect']))
                            ->helperText('Define options for select fields'),

                        Forms\Components\KeyValue::make('rules')
                            ->label('Validation Rules')
                            ->keyLabel('Rule')
                            ->valueLabel('Value')
                            ->helperText('Laravel validation rules (e.g., required, email, min:5)'),

                        Forms\Components\Textarea::make('default_value')
                            ->label('Default Value')
                            ->rows(2)
                            ->helperText('Value to use when no value is set'),

                        Forms\Components\Textarea::make('value')
                            ->label('Current Value')
                            ->rows(2)
                            ->visible(fn (Get $get) => ! $get('is_translatable'))
                            ->helperText('For non-translatable settings only'),

                        Forms\Components\TextInput::make('order')
                            ->label('Sort Order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Lower numbers appear first'),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('key')
                    ->label('Key')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('group')
                    ->label('Group')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('tab')
                    ->label('Tab')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'boolean' => 'success',
                        'select', 'multiselect' => 'info',
                        'json', 'richtext' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\IconColumn::make('is_translatable')
                    ->label('Translatable')
                    ->boolean(),

                Tables\Columns\TextColumn::make('value')
                    ->label('Value')
                    ->limit(30)
                    ->getStateUsing(fn (Setting $record) => $record->is_translatable
                            ? '(translatable)'
                            : ($record->value ?? $record->default_value ?? '—')
                    ),

                Tables\Columns\TextColumn::make('order')
                    ->label('Order')
                    ->sortable(),
            ])
            ->defaultSort('group')
            ->filters([
                Tables\Filters\SelectFilter::make('group')
                    ->options([
                        'general' => 'General',
                        'mail' => 'Mail Settings',
                        'site' => 'Site Settings',
                        'social' => 'Social Media',
                        'seo' => 'SEO',
                        'api' => 'API Settings',
                        'cache' => 'Cache Settings',
                        'security' => 'Security',
                    ]),

                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'text' => 'Text Input',
                        'textarea' => 'Textarea',
                        'number' => 'Number',
                        'boolean' => 'Toggle',
                        'select' => 'Select',
                        'multiselect' => 'Multi Select',
                        'json' => 'JSON',
                        'richtext' => 'Rich Text',
                    ]),

                Tables\Filters\TernaryFilter::make('is_translatable')
                    ->label('Translatable'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Action::make('generate_seeder')
                    ->label('Generate Seeder')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Generate Settings Seeder')
                    ->modalDescription('This will create a new seeder file in your project with all current settings.')
                    ->action(function () {
                        $generator = new SeederGenerator;
                        $path = $generator->generate();

                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Seeder Generated')
                            ->body("Settings seeder created at: {$path}")
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSettings::route('/'),
            'create' => Pages\CreateSetting::route('/create'),
            'edit' => Pages\EditSetting::route('/{record}/edit'),
        ];
    }
}
