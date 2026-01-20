<?php

namespace AdamPatterson\LaravelCsvTranslations\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

class ImportFromCsvCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translation:import 
                            {path? : The path to the CSV file} 
                            {--json : Export as JSON instead of PHP}
                            {--locale= : Import a specific locale}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import translations from a CSV file';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $csvPath = $this->argument('path') ?? config('csv-translations.export_path');

        if (! File::exists($csvPath)) {
            $this->error("CSV file not found at {$csvPath}");

            return self::FAILURE;
        }

        $handle = fopen($csvPath, 'r');
        if ($handle === false) {
            $this->error("Could not open file for reading: {$csvPath}");

            return self::FAILURE;
        }

        fgetcsv($handle); // Skip header

        $localeFilter = $this->option('locale');

        $output = [];

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 3) {
                continue;
            }

            [$path, $key, $original, $new] = array_pad($row, 4, '');

            if ($localeFilter) {
                $segments = explode('/', $path);
                if ($segments[0] === 'vendor') {
                    $rowLocale = $segments[2] ?? null;
                } else {
                    $rowLocale = $segments[0] ?? null;
                }

                if ($rowLocale !== $localeFilter) {
                    continue;
                }
            }

            $translation = ! empty($new) ? $new : $original;

            if (! isset($output[$path])) {
                $output[$path] = [];
            }

            Arr::set($output[$path], $key, $translation);
        }
        fclose($handle);

        foreach ($output as $path => $translations) {
            if ($this->option('json')) {
                $this->saveAsJson($path, $translations);
            } else {
                $this->saveAsPhp($path, $translations);
            }
        }

        $this->info("Imported translations from {$csvPath}");

        return self::SUCCESS;
    }

    protected function saveAsPhp(string $path, array $translations): void
    {
        $fullPath = lang_path($path.'.php');
        $directory = dirname($fullPath);

        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $content = '<?php'.PHP_EOL.PHP_EOL.'return '.$this->prettyPrintArray($translations).';'.PHP_EOL;

        File::put($fullPath, $content);
        $this->line("Created: {$fullPath}");
    }

    protected function saveAsJson(string $path, array $translations): void
    {
        $fullPath = lang_path($path.'.json');
        $directory = dirname($fullPath);

        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $content = json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        File::put($fullPath, $content);
        $this->line("Created: {$fullPath}");
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
