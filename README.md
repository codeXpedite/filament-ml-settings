# Filament Multilingual Settings

A comprehensive multilingual settings management package for Filament Admin Panel with dynamic form generation, translatable fields, and seeder generation.

## Features

- 🌐 **Multilingual Support** - Full integration with Astrotomic Laravel Translatable
- 🎨 **Dynamic Form Builder** - Automatically generates forms based on field types
- 📁 **Organized Structure** - Group settings by categories and tabs
- 🔧 **Multiple Field Types** - Text, Textarea, Number, Toggle, Select, MultiSelect, Color, Date, DateTime, JSON, RichText
- 💾 **Seeder Generation** - Export current settings to seeders
- 🚀 **Caching** - Built-in caching for optimal performance
- ✅ **Validation Rules** - Support for Laravel validation rules
- 🎯 **Filament Integration** - Seamless integration with Filament Admin Panel

## Installation

### Step 1: Register the Service Provider

Add the service provider to your `config/app.php`:

```php
'providers' => [
    // ...
    CodeXpedite\FilamentMlSettings\FilamentMlSettingsServiceProvider::class,
],
```

### Step 2: Register the Plugin in Filament

In your `app/Providers/Filament/AdminPanelProvider.php`, add the plugin:

```php
use CodeXpedite\FilamentMlSettings\FilamentMlSettingsPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugins([
            FilamentMlSettingsPlugin::make(),
        ]);
}
```

### Step 3: Run Migrations

```bash
php artisan migrate
```

### Step 4: Clear Composer Autoload

```bash
composer dump-autoload
```

## Configuration

Publish the configuration file (optional):

```bash
php artisan vendor:publish --tag=filament-ml-settings-config
```

## Usage

### Managing Settings in Filament

1. Navigate to **System → Settings Resource** to create and manage setting definitions
2. Navigate to **System → Settings** to manage setting values

### Creating Settings

Settings can be created through the Filament panel with the following fields:

- **Key**: Unique identifier (e.g., `mail.smtp.host`)
- **Name**: Display name for the setting
- **Group**: Category grouping (e.g., Mail Settings, Site Settings)
- **Tab**: Optional sub-grouping within a group
- **Type**: Field type (text, textarea, number, boolean, etc.)
- **Is Translatable**: Whether the setting supports multiple languages
- **Options**: For select/multiselect fields
- **Validation Rules**: Laravel validation rules
- **Default Value**: Fallback value when not set

### Accessing Settings in Your Application

#### Using the Helper Function

```php
// Get a setting value
$siteName = settings('site.name');

// Get with default value
$timeout = settings('api.timeout', 30);

// Get for specific locale (translatable settings)
$title = settings()->get('site.title', null, 'tr');

// Set a setting value
settings()->set('site.name', 'My Website');

// Set for specific locale
settings()->set('site.title', 'Benim Sitem', 'tr');

// Set multiple values
settings([
    'site.name' => 'My Website',
    'site.description' => 'Welcome to our site'
]);
```

#### Using the Facade

```php
use CodeXpedite\FilamentMlSettings\Facades\Settings;

// Get a setting
$value = Settings::get('mail.from.address');

// Set a setting
Settings::set('mail.from.address', 'info@example.com');

// Check if setting exists
if (Settings::has('mail.smtp.host')) {
    // ...
}

// Get all settings in a group
$mailSettings = Settings::group('mail');

// Clear cache
Settings::clearCache();
```

#### Using the Manager Directly

```php
$settings = app('settings');

// Get grouped settings
$grouped = $settings->getGroupedSettings();

// Create a new setting
$settings->create([
    'key' => 'new.setting',
    'name' => 'New Setting',
    'type' => 'text',
    'group' => 'general'
]);

// Update a setting definition
$settings->update('existing.key', [
    'name' => 'Updated Name'
]);
```

### Field Types

The package supports the following field types:

| Type | Description | Filament Component |
|------|-------------|-------------------|
| `text` | Single line text input | TextInput |
| `textarea` | Multi-line text input | Textarea |
| `number` | Numeric input | TextInput::numeric() |
| `boolean` | Toggle switch | Toggle |
| `select` | Single selection dropdown | Select |
| `multiselect` | Multiple selection dropdown | Select::multiple() |
| `color` | Color picker | ColorPicker |
| `date` | Date picker | DatePicker |
| `datetime` | Date and time picker | DateTimePicker |
| `json` | Key-value pairs | KeyValue |
| `richtext` | Rich text editor | RichEditor |

### Seeder Generation

Generate a seeder from current settings:

#### Via Artisan Command

```bash
php artisan settings:generate-seeder
php artisan settings:generate-seeder --name=CustomSettingsSeeder
```

#### Via Filament Panel

Click the "Generate Seeder" button in either:
- Settings Resource list page
- Manage Settings page

The generated seeder will be created in `database/seeders/` with all current settings and their translations.

### Example Seeder Usage

After generating a seeder, add it to your `DatabaseSeeder.php`:

```php
public function run(): void
{
    $this->call(SettingsSeeder::class);
}
```

## Advanced Features

### Caching

The package includes built-in caching for optimal performance:

```php
// Disable cache temporarily
settings()->disableCache()->get('key');

// Enable cache
settings()->enableCache();

// Set cache time (in seconds)
settings()->setCacheTime(7200); // 2 hours

// Clear all cache
settings()->clearCache();
```

### Validation Rules

Define validation rules for settings:

```php
$settings->create([
    'key' => 'email.admin',
    'name' => 'Admin Email',
    'type' => 'text',
    'rules' => [
        'required' => true,
        'email' => true,
    ]
]);
```

### Translatable Settings

For translatable settings, values are stored per locale:

```php
// Create a translatable setting
$settings->create([
    'key' => 'site.welcome',
    'name' => 'Welcome Message',
    'type' => 'textarea',
    'is_translatable' => true
]);

// Set values for different locales
settings()->set('site.welcome', 'Welcome!', 'en');
settings()->set('site.welcome', 'Hoş Geldiniz!', 'tr');
settings()->set('site.welcome', '¡Bienvenido!', 'es');

// Get value for current locale
$welcome = settings('site.welcome');

// Get value for specific locale
$welcomeTr = settings()->get('site.welcome', null, 'tr');
```

## Database Structure

The package creates two tables:

### settings
- `id` - Primary key
- `key` - Unique setting identifier
- `group` - Setting group
- `tab` - Optional tab within group
- `type` - Field type
- `name` - Display name
- `description` - Help text
- `options` - JSON options for select fields
- `rules` - JSON validation rules
- `default_value` - Default value
- `value` - Value for non-translatable settings
- `is_translatable` - Boolean flag
- `order` - Sort order
- `timestamps`

### setting_translations
- `id` - Primary key
- `setting_id` - Foreign key to settings
- `locale` - Language code
- `value` - Translated value
- `timestamps`

## License

MIT License

## Support

For issues and questions, please open an issue on GitHub.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.