<?php

namespace AdamPatterson\LaravelCsvTranslations\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\SplFileInfo;
use Throwable;

class ExportToCsvCommand extends Command
{
    protected $signature = 'translation:export 
                            {path? : The path to the CSV file}
                            {--l|locales= : Comma separated list of locales to export}
                            {--a|all : Export all locales}';

    protected $description;

    public function __construct()
    {
        parent::__construct();
        $this->setDescription(trans('csv-translations::command.export.description'));
    }

    public function handle(): int
    {
        $csvPath = $this->argument('path') ?? config('csv-translations.export_path');
        $langPath = lang_path();

        if (! File::isDirectory($langPath)) {
            $this->error(trans('csv-translations::command.export.lang_directory_does_not_exist', ['path' => $csvPath]));

            return self::FAILURE;
        }

        $locales = $this->getLocales();
        $rows = $this->collectTranslations($langPath, $locales);

        $writeCsv = $this->writeCsv($csvPath, $rows);

        if ($writeCsv === self::FAILURE) {
            return self::FAILURE;
        }

        $this->info(trans('csv-translations::csv-translations::command.export.exported_translations', ['path' => $csvPath]));

        return self::SUCCESS;
    }

    protected function getLocales(): array
    {
        if ($this->option('all')) {
            return [];
        }

        $localesOption = $this->option('locales');

        return $localesOption
            ? array_map('trim', explode(',', string: $localesOption))
            : [config('app.locale')];
    }

    protected function collectTranslations(string $langPath, array $locales): array
    {
        return collect(File::allFiles($langPath))
            ->filter(fn (SplFileInfo $file) => $file->getExtension() === 'php')
            ->filter(fn (SplFileInfo $file) => $this->shouldIncludeFile($file, $locales))
            ->flatMap(fn (SplFileInfo $file) => $this->extractTranslations($file))
            ->all();
    }

    protected function shouldIncludeFile(SplFileInfo $file, array $locales): bool
    {
        if (empty($locales)) {
            return true;
        }

        $fileLocale = $this->extractLocale(
            $file->getRelativePathname()
        );

        return ! $fileLocale || in_array($fileLocale, $locales, true);
    }

    protected function extractLocale(string $path): ?string
    {
        // Normalize directory separators and trim whitespace
        $normalizedPath = str_replace('\\', '/', trim($path));
        $segments = explode('/', $normalizedPath);

        return $segments[0] === 'vendor' ? ($segments[2] ?? null) : $segments[0];
    }

    protected function extractTranslations(SplFileInfo $file): array
    {
        try {
            $translations = File::getRequire($file->getPathname());
        } catch (Throwable) {
            $this->warn(trans('csv-translations::command.export.could_not_load_translation_file', ['file' => $file->getPathname()]));

            return [];
        }

        if (! is_array($translations)) {
            return [];
        }

        $pathWithoutExtension = str_replace('\\', '/', substr($file->getRelativePathname(), 0, -4));

        return collect(Arr::dot($translations))
            ->filter(fn ($value) => is_string($value) || is_numeric($value) || is_null($value))
            ->map(fn ($value, $key) => [$pathWithoutExtension, $key, (string) $value, ''])
            ->values()
            ->all();
    }

    protected function writeCsv(string $csvPath, array $rows): int
    {
        File::ensureDirectoryExists(dirname($csvPath));

        $handle = fopen($csvPath, 'wb');
        if ($handle === false) {
            $this->error(trans('csv-translations::command.export.could_not_open_file', ['path' => $csvPath]));

            return self::FAILURE;
        }

        try {
            fputcsv($handle, ['Path', 'Key', 'Original', 'New']);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
        } finally {
            fclose($handle);
        }

        return self::SUCCESS;
    }
}
