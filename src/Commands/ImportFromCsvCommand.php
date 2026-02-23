<?php

namespace AdamPatterson\LaravelCsvTranslations\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use JsonException;
use League\Csv\Reader;
use League\Csv\UnavailableStream;

class ImportFromCsvCommand extends Command
{
    protected $signature = 'translation:import 
                            {path? : The path to the CSV file} 
                            {--json : Import as JSON instead of PHP}
                            {--locale= : Import a specific locale}';

    protected $description;

    protected $localeFilter;

    public function __construct()
    {
        parent::__construct();
        $this->setDescription(trans('csv-translations::command.import.description'));
    }

    public function handle(): int
    {
        $csvPath = $this->argument('path') ?? config('csv-translations.export_path');
        $this->localeFilter = $this->option('locale');

        if (! File::exists($csvPath)) {
            $this->error(trans('csv-translations::command.import.csv_file_not_found', ['path' => $csvPath]));

            return self::FAILURE;
        }

        $output = $this->parseCsvFile($csvPath);

        foreach ($output as $path => $translations) {
            try {
                $this->saveTranslations($path, $translations);
            } catch (JsonException $exception) {
                $this->error(trans('csv-translations::command.import.failed_to_save_translations', [
                    'path' => $csvPath,
                    'error' => $exception->getMessage(),
                ]));

                return self::FAILURE;
            }
        }

        $this->info(trans('csv-translations::command.import.imported_translations', ['path' => $csvPath]));

        return self::SUCCESS;
    }

    protected function parseCsvFile(string $csvPath): array
    {
        $localeFilter = $this->option('locale');

        try {
            $csv = Reader::from($csvPath);
            $csv->setHeaderOffset(0);

            $records = $csv->getRecords();
        } catch (UnavailableStream $exception) {
            $this->error(trans('csv-translations::command.import.error_reading_csv_file', [
                'path' => $csvPath,
                'error' => $exception->getMessage(),
            ]));

            return [];
        }

        return collect($records)
            ->filter(fn ($row) => isset($row['Path'], $row['Key'], $row['Original']))
            ->filter(fn ($row) => ! $localeFilter || $this->extractLocale($row['Path']) === $localeFilter)
            ->reduce(function (array $output, array $row) {
                $path = $row['Path'] ?? '';
                $key = $row['Key'] ?? '';
                $original = $row['Original'] ?? '';
                $new = $row['New'] ?? '';
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
        // Normalize backslashes to slashes for locale extraction
        $normalized = str_replace('\\', '/', trim($path));
        $segments = explode('/', $normalized);

        $locale = $segments[0] === 'vendor' ? ($segments[2] ?? null) : $segments[0];

        return $locale ? strtolower(trim($locale)) : null;
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
            $this->error(trans('csv-translations::command.import.skipped_invalid_translation_path', ['path' => $path]));

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
