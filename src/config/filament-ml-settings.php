<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how settings are cached. Set 'enabled' to false to disable
    | caching entirely. The 'ttl' value is in seconds.
    |
    */
    'cache' => [
        'enabled' => true,
        'ttl' => 3600, // 1 hour
        'prefix' => 'settings',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Default Groups
    |--------------------------------------------------------------------------
    |
    | Define the default groups that will be available in the settings.
    | You can add your own groups here.
    |
    */
    'groups' => [
        'general' => 'General',
        'mail' => 'Mail Settings',
        'site' => 'Site Settings',
        'social' => 'Social Media',
        'seo' => 'SEO',
        'api' => 'API Settings',
        'cache' => 'Cache Settings',
        'security' => 'Security',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Field Types
    |--------------------------------------------------------------------------
    |
    | Available field types for settings. You can extend this list
    | with your own custom field types.
    |
    */
    'field_types' => [
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
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Seeder Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the seeder generation settings.
    |
    */
    'seeder' => [
        'path' => database_path('seeders'),
        'namespace' => 'Database\\Seeders',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Navigation Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the navigation settings for Filament panel.
    |
    */
    'navigation' => [
        'group' => 'System',
        'sort' => 100,
        'icon' => 'heroicon-o-cog-6-tooth',
    ],
];