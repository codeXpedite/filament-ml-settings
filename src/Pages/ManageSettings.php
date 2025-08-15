<?php

namespace CodeXpedite\FilamentMlSettings\Pages;

use CodeXpedite\FilamentMlSettings\Models\Setting;
use CodeXpedite\FilamentMlSettings\Services\SeederGenerator;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ManageSettings extends Page implements HasSchemas
{
    use InteractsWithSchemas;
    // Hide this page from navigation as we now use group-specific pages
    protected static bool $shouldRegisterNavigation = false;
    
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-adjustments-horizontal';
    
    protected static string | \UnitEnum | null $navigationGroup = 'System';
    
    protected static ?string $navigationLabel = 'All Settings';
    
    protected static ?int $navigationSort = 8099;
    
    protected string $view = 'filament-ml-settings::pages.settings';
    
    public ?array $data = [];
    
    public function mount(): void
    {
        $this->fillForm();
    }
    
    protected function fillForm(): void
    {
        $settings = Setting::all();
        $data = [];
        
        foreach ($settings as $setting) {
            if ($setting->is_translatable) {
                foreach (config('translatable.locales', ['en']) as $locale) {
                    $data["{$setting->key}_{$locale}"] = $setting->translate($locale)?->value ?? $setting->default_value;
                }
            } else {
                $data[$setting->key] = $setting->value ?? $setting->default_value;
            }
        }
        
        $this->form->fill($data);
    }
    
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components($this->getFormSchema())
            ->statePath('data');
    }
    
    protected function getFormSchema(): array
    {
        $groupedSettings = Setting::getGroupedSettings();
        
        if ($groupedSettings->isEmpty()) {
            return [
                Forms\Components\Placeholder::make('no_settings')
                    ->label('')
                    ->content('No settings have been configured yet. Please add settings through the Settings Resource.')
            ];
        }
        
        $tabs = [];
        
        foreach ($groupedSettings as $group => $tabGroups) {
            $groupLabel = ucfirst(str_replace('_', ' ', $group));
            $tabComponents = [];
            
            if ($tabGroups->keys()->filter(fn($key) => $key !== '')->count() > 0) {
                foreach ($tabGroups as $tab => $settings) {
                    if ($tab === '') continue;
                    
                    $tabLabel = ucfirst(str_replace('_', ' ', $tab));
                    $tabComponents[] = Tab::make($tabLabel)
                        ->schema($this->buildFieldsForSettings($settings));
                }
                
                if ($tabGroups->has('')) {
                    $tabComponents[] = Tab::make('General')
                        ->schema($this->buildFieldsForSettings($tabGroups['']));
                }
                
                $tabs[] = Tab::make($groupLabel)
                    ->schema([
                        Tabs::make('sub_tabs')
                            ->tabs($tabComponents)
                    ]);
            } else {
                $tabs[] = Tab::make($groupLabel)
                    ->schema($this->buildFieldsForSettings($tabGroups->flatten()));
            }
        }
        
        return [
            Tabs::make('Settings')
                ->tabs($tabs)
                ->persistTabInQueryString()
        ];
    }
    
    protected function buildFieldsForSettings(Collection $settings): array
    {
        $fields = [];
        
        foreach ($settings as $setting) {
            if ($setting->is_translatable) {
                $translatableFields = [];
                $locales = config('translatable.locales', ['en']);
                
                foreach ($locales as $locale) {
                    $field = $this->createFieldForSetting($setting, "_{$locale}");
                    if ($field) {
                        $field->label($setting->formatted_name . " ({$locale})");
                        $translatableFields[] = $field;
                    }
                }
                
                if (!empty($translatableFields)) {
                    $fields[] = \Filament\Schemas\Components\Section::make($setting->formatted_name)
                        ->description($setting->description)
                        ->schema($translatableFields)
                        ->collapsible();
                }
            } else {
                $field = $this->createFieldForSetting($setting);
                if ($field) {
                    $fields[] = $field;
                }
            }
        }
        
        return $fields;
    }
    
    protected function createFieldForSetting(Setting $setting, string $suffix = '')
    {
        $fieldName = $setting->key . $suffix;
        
        $field = match ($setting->type) {
            'text' => Forms\Components\TextInput::make($fieldName),
            'textarea' => Forms\Components\Textarea::make($fieldName)->rows(3),
            'number' => Forms\Components\TextInput::make($fieldName)->numeric(),
            'boolean', 'toggle' => Forms\Components\Toggle::make($fieldName),
            'select' => Forms\Components\Select::make($fieldName)
                ->options($setting->options ?? []),
            'multiselect' => Forms\Components\Select::make($fieldName)
                ->multiple()
                ->options($setting->options ?? []),
            'color' => Forms\Components\ColorPicker::make($fieldName),
            'date' => Forms\Components\DatePicker::make($fieldName),
            'datetime' => Forms\Components\DateTimePicker::make($fieldName),
            'json' => Forms\Components\KeyValue::make($fieldName),
            'richtext' => Forms\Components\RichEditor::make($fieldName),
            default => Forms\Components\TextInput::make($fieldName),
        };
        
        if (!$suffix) {
            $field->label($setting->formatted_name);
        }
        
        if ($setting->description) {
            $field->helperText($setting->description);
        }
        
        if ($setting->rules) {
            $rules = is_array($setting->rules) ? $setting->rules : explode('|', $setting->rules);
            
            foreach ($rules as $rule) {
                if (str_starts_with($rule, 'required')) {
                    $field->required();
                }
                if (str_starts_with($rule, 'email')) {
                    $field->email();
                }
                if (str_starts_with($rule, 'numeric')) {
                    $field->numeric();
                }
                if (str_starts_with($rule, 'min:')) {
                    $min = (int) str_replace('min:', '', $rule);
                    $field->minLength($min);
                }
                if (str_starts_with($rule, 'max:')) {
                    $max = (int) str_replace('max:', '', $rule);
                    $field->maxLength($max);
                }
            }
        }
        
        return $field;
    }
    
    public function save(): void
    {
        $data = $this->form->getState();
        $settings = Setting::all();
        
        foreach ($settings as $setting) {
            if ($setting->is_translatable) {
                foreach (config('translatable.locales', ['en']) as $locale) {
                    $key = "{$setting->key}_{$locale}";
                    if (isset($data[$key])) {
                        $setting->setValue($data[$key], $locale);
                    }
                }
            } else {
                if (isset($data[$setting->key])) {
                    $setting->setValue($data[$setting->key]);
                }
            }
        }
        
        Cache::flush();
        
        Notification::make()
            ->success()
            ->title('Settings saved')
            ->body('All settings have been saved successfully.')
            ->send();
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate_seeder')
                ->label('Generate Seeder')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Generate Settings Seeder')
                ->modalDescription('This will create a new seeder file in your project with all current settings.')
                ->action(function () {
                    $generator = new SeederGenerator();
                    $path = $generator->generate();
                    
                    Notification::make()
                        ->success()
                        ->title('Seeder Generated')
                        ->body("Settings seeder created at: {$path}")
                        ->send();
                }),
                
            Action::make('clear_cache')
                ->label('Clear Cache')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function () {
                    Cache::flush();
                    
                    Notification::make()
                        ->success()
                        ->title('Cache Cleared')
                        ->body('Settings cache has been cleared.')
                        ->send();
                }),
        ];
    }
}