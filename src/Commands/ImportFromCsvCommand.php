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

    protected $description = 'Update Laravel translations from a CSV file.';

    public function handle(): int
    {
        $csvPath = $this->argument('path') ?? config('csv-translations.export_path');

        if (! File::exists($csvPath)) {
            $this->error("CSV file not found at {$csvPath}");

            return self::FAILURE;
        }

        $output = $this->parseCsvFile($csvPath);

        foreach ($output as $path => $translations) {
            try {
                $this->saveTranslations($path, $translations);
            } catch (JsonException $exception) {
                $this->error("Failed to save translations for '{$path}': ".$exception->getMessage());

                return self::FAILURE;
            }
        }

        $this->info("Imported translations from {$csvPath}");

        return self::SUCCESS;
    }

    protected function parseCsvFile(string $csvPath): array
    {
        $localeFilter = $this->option('locale');
        $handle = fopen($csvPath, 'rb');

        if ($handle === false) {
            return [];
        }

        try {
            // Skip header row
            fgetcsv($handle, 0, ',', '"', '\\');

            $output = [];

            while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
                if (count($row) < 3) {
                    continue;
                }

                if ($localeFilter && $this->extractLocale($row[0]) !== $localeFilter) {
                    continue;
                }

                [$path, $key, $original, $new] = array_pad($row, 4, '');
                $translation = $new !== '' ? $new : $original;

                if (! isset($output[$path])) {
                    $output[$path] = [];
                }

                Arr::set($output[$path], $key, $translation);
            }

            return $output;
        } finally {
            fclose($handle);
        }
    }

    protected function extractLocale(string $path): ?string
    {
        $normalizedPath = str_replace('\\', '/', $path);
        $segments = explode('/', $normalizedPath);

        return $segments[0] === 'vendor' ? ($segments[2] ?? null) : $segments[0];
    }

    /**
     * Sanitize and validate a relative translation path from CSV input.
     * Returns a safe relative path (without extension) or null if invalid.
     */
    protected function sanitizeTranslationPath(string $path): ?string
    {
        // Normalize directory separators and trim whitespace
        $normalized = str_replace('\\', '/', trim($path));

        // Disallow empty paths
        if ($normalized === '') {
            return null;
        }

        // Disallow absolute paths
        if (strpos($normalized, '/') === 0) {
            return null;
        }

        // Resolve segments and detect traversal
        $segments = explode('/', $normalized);
        $safeParts = [];
        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                // Directory traversal attempt
                return null;
            }
            $safeParts[] = $segment;
        }

        if (empty($safeParts)) {
            return null;
        }

        return implode('/', $safeParts);
    }

    /**
     * @throws JsonException
     */
    protected function saveTranslations(string $path, array $translations): void
    {
        $sanitizedPath = $this->sanitizeTranslationPath($path);

        if ($sanitizedPath === null) {
            $this->error("Skipped invalid translation path: {$path}");

            return;
        }

        $extension = $this->option('json') ? 'json' : 'php';
        $baseLangPath = lang_path();
        $fullPath = $baseLangPath.DIRECTORY_SEPARATOR.$sanitizedPath.'.'.$extension;

        // Ensure the target directory exists
        File::ensureDirectoryExists(dirname($fullPath));

        $content = $this->option('json')
            ? json_encode($translations, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            : '<?php'.PHP_EOL.PHP_EOL.'return '.$this->prettyPrintArray($translations).';'.PHP_EOL;

        File::put($fullPath, $content);
        $this->line("Created: $fullPath");
    }

    protected function prettyPrintArray(array $array, int $indent = 0): string
    {
        $result = '['.PHP_EOL;
        $spaces = str_repeat('    ', $indent + 1);
        foreach ($array as $key => $value) {
            $result .= $spaces.var_export($key, true).' => ';
            if (is_array($value)) {
                $result .= $this->prettyPrintArray($value, $indent + 1);
            } else {
                $result .= var_export($value, true);
            }
            $result .= ','.PHP_EOL;
        }
        $result .= str_repeat('    ', $indent).']';

        return $result;
    }
}
