<?php

namespace CodeXpedite\FilamentMlSettings\Services;

use CodeXpedite\FilamentMlSettings\Models\Setting;

class SeederGenerator
{
    public function generate(?string $className = null): string
    {
        $className = $className ?? 'SettingsSeeder';
        $timestamp = date('Y_m_d_His');
        $filename = "{$timestamp}_{$className}.php";
        $path = database_path("seeders/{$filename}");

        $content = $this->generateSeederContent($className);

        file_put_contents($path, $content);

        return $path;
    }

    protected function generateSeederContent(string $className): string
    {
        $settings = Setting::with('translations')->get();

        $settingsArray = [];
        $translationsArray = [];

        foreach ($settings as $setting) {
            $settingData = [
                'key' => $setting->key,
                'group' => $setting->group,
                'tab' => $setting->tab,
                'type' => $setting->type,
                'name' => $setting->name,
                'description' => $setting->description,
                'options' => $setting->options,
                'rules' => $setting->rules,
                'default_value' => $setting->default_value,
                'value' => $setting->value,
                'is_translatable' => $setting->is_translatable,
                'order' => $setting->order,
            ];

            $settingsArray[] = $this->arrayToString($settingData, 3);

            if ($setting->is_translatable && $setting->translations->isNotEmpty()) {
                foreach ($setting->translations as $translation) {
                    $translationData = [
                        'key' => $setting->key,
                        'locale' => $translation->locale,
                        'value' => $translation->value,
                    ];

                    $translationsArray[] = $this->arrayToString($translationData, 3);
                }
            }
        }

        $settingsString = implode(",\n", $settingsArray);
        $translationsString = implode(",\n", $translationsArray);

        return <<<PHP
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use CodeXpedite\FilamentMlSettings\Models\Setting;
use Illuminate\Support\Facades\DB;

class {$className} extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // Clear existing settings
            Setting::query()->delete();
            
            // Insert settings
            \$settings = [
{$settingsString}
            ];
            
            foreach (\$settings as \$settingData) {
                Setting::create(\$settingData);
            }
            
            // Insert translations
            \$translations = [
{$translationsString}
            ];
            
            foreach (\$translations as \$translationData) {
                \$setting = Setting::where('key', \$translationData['key'])->first();
                if (\$setting && \$setting->is_translatable) {
                    \$setting->translateOrNew(\$translationData['locale'])->value = \$translationData['value'];
                    \$setting->save();
                }
            }
        });
    }
}
PHP;
    }

    protected function arrayToString(array $array, int $indent = 0): string
    {
        $indentStr = str_repeat('    ', $indent);
        $result = $indentStr."[\n";

        foreach ($array as $key => $value) {
            $result .= $indentStr.'    '.$this->formatKeyValue($key, $value).",\n";
        }

        $result .= $indentStr.']';

        return $result;
    }

    protected function formatKeyValue($key, $value): string
    {
        $key = "'{$key}'";

        if ($value === null) {
            return "{$key} => null";
        }

        if (is_bool($value)) {
            return "{$key} => ".($value ? 'true' : 'false');
        }

        if (is_array($value)) {
            $json = json_encode($value);

            return "{$key} => "."json_decode('".addslashes($json)."', true)";
        }

        if (is_numeric($value)) {
            return "{$key} => {$value}";
        }

        return "{$key} => '".addslashes($value)."'";
    }
}
