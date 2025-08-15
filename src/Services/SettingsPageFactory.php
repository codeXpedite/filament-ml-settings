<?php

namespace CodeXpedite\FilamentMlSettings\Services;

use CodeXpedite\FilamentMlSettings\Pages\BaseGroupSettings;

/**
 * Factory for creating dynamic settings pages
 * This allows creating settings pages without pre-defining classes
 */
class SettingsPageFactory
{
    /**
     * Create a dynamic settings page class for a group
     */
    public static function createForGroup(
        string $group,
        string $label = null,
        string $icon = null,
        int $sort = 99
    ): string {
        $className = 'DynamicSettings' . ucfirst($group);
        $fullClassName = 'CodeXpedite\\FilamentMlSettings\\Pages\\Dynamic\\' . $className;
        
        // If class already exists, return it
        if (class_exists($fullClassName)) {
            return $fullClassName;
        }
        
        // Create the class dynamically
        $label = $label ?? ucfirst(str_replace(['_', '-'], ' ', $group));
        $icon = $icon ?? 'heroicon-o-cog';
        $slug = 'manage-settings/' . $group;
        
        // Define the class using eval (use with caution)
        $classDefinition = "
        namespace CodeXpedite\\FilamentMlSettings\\Pages\\Dynamic;
        
        class {$className} extends \\CodeXpedite\\FilamentMlSettings\\Pages\\BaseGroupSettings
        {
            protected static string | \\BackedEnum | null \$navigationIcon = '{$icon}';
            protected static ?int \$navigationSort = {$sort};
            protected static ?string \$slug = '{$slug}';
            
            protected function getSettingsGroup(): string
            {
                return '{$group}';
            }
            
            protected function getGroupLabel(): string
            {
                return '{$label}';
            }
        }
        ";
        
        eval($classDefinition);
        
        return $fullClassName;
    }
    
    /**
     * Register multiple dynamic groups at once
     */
    public static function registerGroups(array $groups): array
    {
        $classes = [];
        
        foreach ($groups as $group => $config) {
            if (is_string($config)) {
                // Simple format: ['payment' => 'Payment']
                $classes[] = self::createForGroup($group, $config);
            } elseif (is_array($config)) {
                // Full format: ['payment' => ['label' => 'Payment', 'icon' => '...', 'sort' => 7]]
                $classes[] = self::createForGroup(
                    $group,
                    $config['label'] ?? null,
                    $config['icon'] ?? null,
                    $config['sort'] ?? 99
                );
            }
        }
        
        return $classes;
    }
}