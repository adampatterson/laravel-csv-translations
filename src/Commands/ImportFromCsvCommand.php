<?php

namespace AdamPatterson\LaravelCsvTranslations\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use JsonException;

class ImportFromCsvCommand extends Command
{
    protected $signature = 'translation:import 
                            {path? : The path to the CSV file} 
                            {--json : Import as JSON instead of PHP}
                            {--locale= : Import a specific locale}';

    protected $description = 'Create updated Laravel translations from a CSV file.';

    /**
     * @throws JsonException
     */
    public function handle(): int
    {
        $csvPath = $this->argument('path') ?? config('csv-translations.export_path');

        if (! File::exists($csvPath)) {
            $this->error("CSV file not found at $csvPath");

            return self::FAILURE;
        }

        $output = $this->parseCsvFile($csvPath);

        foreach ($output as $path => $translations) {
            $this->saveTranslations($path, $translations);
        }

        $this->info("Imported translations from $csvPath");

        return self::SUCCESS;
    }

    protected function parseCsvFile(string $csvPath): array
    {
        $localeFilter = $this->option('locale');

        return collect(file($csvPath))
            ->skip(1) // Skip header
            ->map(fn ($line) => str_getcsv($line))
            ->filter(fn ($row) => count($row) >= 3)
            ->filter(fn ($row) => ! $localeFilter || $this->extractLocale($row[0]) === $localeFilter)
            ->reduce(function (array $output, array $row) {
                [$path, $key, $original, $new] = array_pad($row, 4, '');
                $translation = $new !== '' ? $new : $original;

                if (! isset($output[$path])) {
                    $output[$path] = [];
                }
                Arr::set($output[$path], $key, $translation);

                return $output;
            }, []);
    }

    protected function extractLocale(string $path): ?string
    {
        $segments = explode('/', $path);

        return $segments[0] === 'vendor' ? ($segments[2] ?? null) : $segments[0];
    }

    /**
     * @throws JsonException
     */
    protected function saveTranslations(string $path, array $translations): void
    {
        $extension = $this->option('json') ? 'json' : 'php';
        $fullPath = lang_path("$path.$extension");

        File::ensureDirectoryExists(dirname($fullPath));

        $content = $this->option('json')
            ? json_encode($translations, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            : '<?php'.PHP_EOL.PHP_EOL.'return '.var_export($translations, true).';'.PHP_EOL;

        File::put($fullPath, $content);
        $this->line("Created: $fullPath");
    }
}
