<?php

namespace CodeXpedite\FilamentMlSettings\Commands;

use CodeXpedite\FilamentMlSettings\Services\SeederGenerator;
use Illuminate\Console\Command;

class GenerateSettingsSeederCommand extends Command
{
    protected $signature = 'settings:generate-seeder {--name=SettingsSeeder : The name of the seeder class}';

    protected $description = 'Generate a seeder file from current settings';

    public function handle(): int
    {
        $name = $this->option('name');

        $this->info('Generating settings seeder...');

        try {
            $generator = new SeederGenerator;
            $path = $generator->generate($name);

            $this->info('Settings seeder generated successfully!');
            $this->line("Path: {$path}");
            $this->line('');
            $this->info("Don't forget to add the seeder to your DatabaseSeeder.php:");
            $this->line("\$this->call({$name}::class);");

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to generate seeder: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
