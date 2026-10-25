<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Yaml\Yaml;

class GenerateApiDocumentation extends Command
{
    protected $signature = 'docs:generate';

    protected $description = 'Generate Scribe HTML, Postman, and OpenAPI documentation from source';

    public function handle(): int
    {
        if ($this->call('scribe:generate', ['--force' => true, '--no-interaction' => true]) !== self::SUCCESS) {
            return self::FAILURE;
        }

        $specification = Yaml::parseFile(public_path('docs/openapi.yaml'));
        $json = json_encode($specification, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL;
        file_put_contents(base_path('docs/openapi.json'), $json);
        file_put_contents(public_path('docs/openapi.json'), $json);
        $this->info('Exported the generated OpenAPI JSON to docs/openapi.json.');

        return self::SUCCESS;
    }
}
