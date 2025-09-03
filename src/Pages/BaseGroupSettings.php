<?php

namespace CodeXpedite\FilamentMlSettings\Pages;

use Astrotomic\Translatable\Locales;
use CodeXpedite\FilamentMlSettings\Models\Setting;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

abstract class BaseGroupSettings extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 8000;

    protected string $view = 'filament-ml-settings::pages.settings';

    public ?array $data = [];

    // Each child class must define its group
    abstract protected function getSettingsGroup(): string;

    // Get the label for this settings group
    abstract protected function getGroupLabel(): string;

    public static function getNavigationLabel(): string
    {
        return (new static)->getGroupLabel();
    }

    public static function getNavigationBadge(): ?string
    {
        return null;
    }

    public function getTitle(): string
    {
        return $this->getGroupLabel().' Settings';
    }

    public function mount(): void
    {
        $this->fillForm();
    }

    protected function fillForm(): void
    {
        $settings = Setting::where('group', $this->getSettingsGroup())->get();
        $data = [];

        // Get properly formatted locales from Astrotomic Locales service
        $locales = app(Locales::class)->all();

        foreach ($settings as $setting) {
            if ($setting->is_translatable) {
                foreach ($locales as $locale) {
                    $translatedValue = $setting->translate($locale)?->value;
                    $defaultValue = $setting->default_value;

                    // Handle array/json default values
                    if (in_array($setting->type, ['json', 'multiselect', 'array']) && is_array($defaultValue)) {
                        $defaultValue = json_encode($defaultValue);
                    }

                    $data["{$setting->key}_{$locale}"] = $translatedValue ?? $defaultValue;
                }
            } else {
                $value = $setting->value ?? $setting->default_value;

                // For non-translatable settings, the value should already be properly cast
                // by the model's accessor, so we can use it directly
                $data[$setting->key] = $value;
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
        $settings = Setting::where('group', $this->getSettingsGroup())
            ->with('translations')
            ->ordered()
            ->get();

        if ($settings->isEmpty()) {
            return [
                Forms\Components\Placeholder::make('no_settings')
                    ->label('')
                    ->content('No settings have been configured for this group yet.'),
            ];
        }

        // Group by tab
        $tabGroups = $settings->groupBy('tab');

        // If there are tabs defined, create tab structure
        if ($tabGroups->keys()->filter(fn ($key) => $key !== '' && $key !== null)->count() > 0) {
            $tabs = [];

            // Add tabs with content
            foreach ($tabGroups as $tab => $tabSettings) {
                if ($tab === '' || $tab === null) {
                    continue;
                }

                $tabLabel = ucfirst(str_replace('_', ' ', $tab));
                $tabs[] = Tab::make($tabLabel)
                    ->schema($this->buildFieldsForSettings($tabSettings));
            }

            // Add general tab if there are settings without a tab
            if ($tabGroups->has('') || $tabGroups->has(null)) {
                $generalSettings = $tabGroups->get('') ?? collect();
                $nullSettings = $tabGroups->get(null) ?? collect();
                $combinedSettings = $generalSettings->merge($nullSettings);

                if ($combinedSettings->isNotEmpty()) {
                    $tabs[] = Tab::make('General')
                        ->schema($this->buildFieldsForSettings($combinedSettings));
                }
            }

            return [
                Tabs::make('Settings')
                    ->tabs($tabs)
                    ->persistTabInQueryString(),
            ];
        }

        // No tabs, just return fields directly
        return $this->buildFieldsForSettings($settings);
    }

    protected function buildFieldsForSettings(Collection $settings): array
    {
        $fields = [];

        // Get properly formatted locales from Astrotomic Locales service
        $locales = app(Locales::class)->all();

        foreach ($settings as $setting) {
            if ($setting->is_translatable) {
                $translatableFields = [];

                foreach ($locales as $locale) {
                    $field = $this->createFieldForSetting($setting, "_{$locale}");
                    if ($field) {
                        $field->label($setting->formatted_name." ({$locale})");
                        $translatableFields[] = $field;
                    }
                }

                if (! empty($translatableFields)) {
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
        $fieldName = $setting->key.$suffix;

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
            'email' => Forms\Components\TextInput::make($fieldName)->email(),
            'url' => Forms\Components\TextInput::make($fieldName)->url(),
            'password' => Forms\Components\TextInput::make($fieldName)->password(),
            default => Forms\Components\TextInput::make($fieldName),
        };

        if (! $suffix) {
            $field->label($setting->formatted_name);
        }

        if ($setting->description) {
            $field->helperText($setting->description);
        }

        // Apply validation rules
        if ($setting->rules) {
            $rules = is_array($setting->rules) ? $setting->rules : explode('|', $setting->rules);

            foreach ($rules as $rule) {
                if (str_starts_with($rule, 'required')) {
                    $field->required();
                }
                if (str_starts_with($rule, 'email')) {
                    $field->email();
                }
                if (str_starts_with($rule, 'url')) {
                    $field->url();
                }
                if (str_starts_with($rule, 'numeric')) {
                    $field->numeric();
                }
                if (str_starts_with($rule, 'min:')) {
                    $min = (int) str_replace('min:', '', $rule);
                    if (method_exists($field, 'minLength')) {
                        $field->minLength($min);
                    } elseif (method_exists($field, 'minValue')) {
                        $field->minValue($min);
                    }
                }
                if (str_starts_with($rule, 'max:')) {
                    $max = (int) str_replace('max:', '', $rule);
                    if (method_exists($field, 'maxLength')) {
                        $field->maxLength($max);
                    } elseif (method_exists($field, 'maxValue')) {
                        $field->maxValue($max);
                    }
                }
            }
        }

        return $field;
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $settings = Setting::where('group', $this->getSettingsGroup())->get();

        // Get properly formatted locales from Astrotomic Locales service
        $locales = app(Locales::class)->all();

        foreach ($settings as $setting) {
            if ($setting->is_translatable) {
                foreach ($locales as $locale) {
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
            ->body($this->getGroupLabel().' settings have been saved successfully.')
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
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
