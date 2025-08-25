<?php

namespace CodeXpedite\FilamentMlSettings;

use CodeXpedite\FilamentMlSettings\Resources\SettingResource;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Illuminate\Support\Facades\Schema;

class FilamentMlSettingsPlugin implements Plugin
{
    protected bool $hasSettingResource = true;
    protected bool $hasManageSettingsPage = true;
    protected bool $hideSettingResourceInProduction = true;
    protected array $customGroups = [];

    public function getId(): string
    {
        return 'filament-ml-settings';
    }

    public function register(Panel $panel): void
    {
        // Register SettingResource based on configuration
        if ($this->hasSettingResource) {
            // Check if we should hide in production
            $shouldRegister = !$this->hideSettingResourceInProduction || 
                              app()->environment(['local', 'development']);
            
            if ($shouldRegister) {
                $panel->resources([
                    SettingResource::class,
                ]);
            }
        }

        if ($this->hasManageSettingsPage) {
            // Register dynamic pages for each settings group
            $this->registerSettingsPages($panel);
        }
    }
    
    protected function registerSettingsPages(Panel $panel): void
    {
        // Define available settings pages
        $settingsPages = [
            \CodeXpedite\FilamentMlSettings\Pages\Groups\GeneralSettings::class,
            \CodeXpedite\FilamentMlSettings\Pages\Groups\SiteSettings::class,
            \CodeXpedite\FilamentMlSettings\Pages\Groups\MailSettings::class,
            \CodeXpedite\FilamentMlSettings\Pages\Groups\ApiSettings::class,
            \CodeXpedite\FilamentMlSettings\Pages\Groups\ReadingSettings::class,
            \CodeXpedite\FilamentMlSettings\Pages\Groups\SocialSettings::class,
        ];
        
        // Register pages based on available settings groups
        try {
            // Check if database connection exists and table is available
            if (Schema::hasTable('settings')) {
                $availableGroups = \CodeXpedite\FilamentMlSettings\Models\Setting::distinct('group')
                    ->pluck('group')
                    ->filter()
                    ->values()
                    ->toArray();
            } else {
                // If table doesn't exist, register all default groups
                $availableGroups = ['general', 'site', 'mail', 'api', 'reading', 'social'];
            }
        } catch (\Exception $e) {
            // If any database error occurs, register all pages as fallback
            $availableGroups = ['general', 'site', 'mail', 'api', 'reading', 'social'];
        }
        
        $pagesToRegister = [];
        foreach ($settingsPages as $pageClass) {
            // Use reflection to check if this page should be registered
            try {
                $reflection = new \ReflectionClass($pageClass);
                $instance = $reflection->newInstanceWithoutConstructor();
                
                // Check if getSettingsGroup method exists
                if ($reflection->hasMethod('getSettingsGroup')) {
                    $method = $reflection->getMethod('getSettingsGroup');
                    $method->setAccessible(true);
                    $group = $method->invoke($instance);
                    
                    // Only register if this group has settings
                    if ($group && in_array($group, $availableGroups)) {
                        $pagesToRegister[] = $pageClass;
                    }
                }
            } catch (\Exception $e) {
                // Skip this page if there's an error
                continue;
            }
        }
        
        // Register the filtered pages
        if (!empty($pagesToRegister)) {
            $panel->pages($pagesToRegister);
        }
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public function settingResource(bool $condition = true): static
    {
        $this->hasSettingResource = $condition;
        return $this;
    }

    public function manageSettingsPage(bool $condition = true): static
    {
        $this->hasManageSettingsPage = $condition;
        return $this;
    }
    
    public function hideSettingResourceInProduction(bool $condition = true): static
    {
        $this->hideSettingResourceInProduction = $condition;
        return $this;
    }
    
    public function withCustomGroups(array $groups): static
    {
        $this->customGroups = $groups;
        return $this;
    }
}