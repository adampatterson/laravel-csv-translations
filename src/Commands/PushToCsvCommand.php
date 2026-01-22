<?php

namespace AdamPatterson\LaravelCsvTranslations\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

class PushToCsvCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translation:push 
                            {path? : The path to the CSV file}
                            {--l|locales= : Comma separated list of locales to export}
                            {--a|all : Export all locales}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Push all published translations to CSV file.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $csvPath = $this->argument('path') ?? config('csv-translations.export_path');

        $langPath = lang_path();

        if (! File::isDirectory($langPath)) {
            $this->error("The lang directory does not exist at {$langPath}");

            return self::FAILURE;
        }

        $allOption = $this->option('all');
        $localesOption = $this->option('locales');
        $locales = $localesOption
            ? explode(',', $localesOption)
            : [config('app.locale')];

        $locales = array_map('trim', $locales);

        $files = File::allFiles($langPath);
        $data = [['Path', 'Key', 'Original', 'New']];

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = $file->getRelativePathname();
            $segments = explode(DIRECTORY_SEPARATOR, $relativePath);

            if ($segments[0] === 'vendor') {
                $fileLocale = $segments[2] ?? null;
            } else {
                $fileLocale = $segments[0] ?? null;
            }

            if (! $allOption && $fileLocale && ! in_array($fileLocale, $locales)) {
                continue;
            }

            $pathWithoutExtension = substr($relativePath, 0, -4);

            try {
                $translations = File::getRequire($file->getPathname());
            } catch (\Exception $e) {
                $this->warn("Could not load translation file: {$file->getPathname()}");

                continue;
            }

            if (! is_array($translations)) {
                continue;
            }

            $dotTranslations = Arr::dot($translations);

            foreach ($dotTranslations as $key => $value) {
                if (! is_string($value) && ! is_numeric($value) && ! is_null($value)) {
                    continue;
                }

                $data[] = [$pathWithoutExtension, $key, (string) $value, ''];
            }
        }

        $directory = dirname($csvPath);
        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $handle = fopen($csvPath, 'w');
        if ($handle === false) {
            $this->error("Could not open file for writing: {$csvPath}");

            return self::FAILURE;
        }

        foreach ($data as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);

        $this->info("Exported translations to {$csvPath}");

        return self::SUCCESS;
    }
}
