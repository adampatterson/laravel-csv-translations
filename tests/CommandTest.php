<?php

namespace AdamPatterson\LaravelCsvTranslations\Tests;

use Illuminate\Support\Facades\File;

beforeEach(function () {
    // Clean up lang directory before each test
    $langPath = lang_path();
    if (File::isDirectory($langPath)) {
        File::deleteDirectory($langPath);
    }
});

afterEach(function () {
    // Clean up lang directory after each test
    $langPath = lang_path();
    if (File::isDirectory($langPath)) {
        File::deleteDirectory($langPath);
    }
});

it('can export translations to CSV', function () {
    // Setup: Create dummy translation files
    $langPath = lang_path();
    File::makeDirectory($langPath.'/en', 0755, true);

    File::put($langPath.'/en/auth.php', "<?php return ['failed' => 'Failed', 'nested' => ['key' => 'Value']];");

    $csvPath = base_path('test_translations.csv');

    $this->artisan('translation:export', ['path' => $csvPath])
        ->assertExitCode(0);

    $this->assertFileExists($csvPath);

    $rows = array_map('str_getcsv', file($csvPath));

    expect($rows)->toHaveCount(3); // Header + 2 translations
    expect($rows[0])->toBe(['Path', 'Key', 'Original', 'New']);
    expect($rows[1])->toBe(['en/auth', 'failed', 'Failed', '']);
    expect($rows[2])->toBe(['en/auth', 'nested.key', 'Value', '']);

    // Cleanup
    File::delete($csvPath);
});

it('can filter exported translations by locale', function () {
    $langPath = lang_path();
    File::makeDirectory($langPath.'/en', 0755, true);
    File::makeDirectory($langPath.'/fr', 0755, true);
    File::makeDirectory($langPath.'/vendor/package/es', 0755, true);

    File::put($langPath.'/en/test.php', "<?php return ['hello' => 'Hello'];");
    File::put($langPath.'/fr/test.php', "<?php return ['hello' => 'Bonjour'];");
    File::put($langPath.'/vendor/package/es/test.php', "<?php return ['hello' => 'Hola'];");

    $csvPath = base_path('filter_test.csv');

    // Test exporting only 'fr' and 'es'
    $this->artisan('translation:export', [
        'path' => $csvPath,
        '--locales' => 'fr,es',
    ])->assertExitCode(0);

    $rows = array_map('str_getcsv', file($csvPath));
    $paths = array_column($rows, 0);

    expect($paths)->toContain('fr/test')
        ->and($paths)->toContain('vendor/package/es/test')
        ->and($paths)->not->toContain('en/test');

    // Cleanup
    File::delete($csvPath);
});

it('can export all translations', function () {
    $langPath = lang_path();
    File::makeDirectory($langPath.'/en', 0755, true);
    File::makeDirectory($langPath.'/fr', 0755, true);

    File::put($langPath.'/en/test.php', "<?php return ['hello' => 'Hello'];");
    File::put($langPath.'/fr/test.php', "<?php return ['hello' => 'Bonjour'];");

    $csvPath = base_path('all_test.csv');

    $this->artisan('translation:export', [
        'path' => $csvPath,
        '--all' => true,
    ])->assertExitCode(0);

    $rows = array_map('str_getcsv', file($csvPath));
    $paths = array_column($rows, 0);

    expect($paths)->toContain('en/test')
        ->and($paths)->toContain('fr/test');

    // Cleanup
    File::delete($csvPath);
});

it('exports only base locale by default', function () {
    $langPath = lang_path();
    File::makeDirectory($langPath.'/en', 0755, true);
    File::makeDirectory($langPath.'/fr', 0755, true);

    File::put($langPath.'/en/test.php', "<?php return ['hello' => 'Hello'];");
    File::put($langPath.'/fr/test.php', "<?php return ['hello' => 'Bonjour'];");

    $csvPath = base_path('default_test.csv');

    // Assuming default locale is 'en'
    $this->artisan('translation:export', ['path' => $csvPath])
        ->assertExitCode(0);

    $rows = array_map('str_getcsv', file($csvPath));
    $paths = array_column($rows, 0);

    expect($paths)->toContain('en/test')
        ->and($paths)->not->toContain('fr/test');

    // Cleanup
    File::delete($csvPath);
});

it('can import translations from CSV', function () {
    $csvPath = base_path('import_test.csv');
    $content = "Path,Key,Original,New\n";
    $content .= "en/test,greeting,Old Hello,Hello\n";
    $content .= "en/test,nested.key,Old Value,Value\n";
    $content .= "vendor/package/en/modal,close,Old Close,Close\n";

    File::put($csvPath, $content);

    $this->artisan('translation:import', ['path' => $csvPath])
        ->assertExitCode(0);

    $langPath = lang_path();
    $this->assertFileExists($langPath.'/en/test.php');
    $this->assertFileExists($langPath.'/vendor/package/en/modal.php');

    $testTranslations = include $langPath.'/en/test.php';
    expect($testTranslations)->toBe([
        'greeting' => 'Hello',
        'nested' => [
            'key' => 'Value',
        ],
    ]);

    $vendorTranslations = include $langPath.'/vendor/package/en/modal.php';
    expect($vendorTranslations)->toBe([
        'close' => 'Close',
    ]);

    // Cleanup
    File::delete($csvPath);
});

it('can import a specific locale', function () {
    $csvPath = base_path('import_locale_test.csv');
    $content = "Path,Key,Original,New\n";
    $content .= "en/test,greeting,Old Hello,Hello\n";
    $content .= "fr/test,greeting,Old Bonjour,Bonjour\n";

    File::put($csvPath, $content);

    $this->artisan('translation:import', ['path' => $csvPath, '--locale' => 'fr'])
        ->assertExitCode(0);

    $langPath = lang_path();
    $this->assertFileDoesNotExist($langPath.'/en/test.php');
    $this->assertFileExists($langPath.'/fr/test.php');

    $frTranslations = include $langPath.'/fr/test.php';
    expect($frTranslations)->toBe(['greeting' => 'Bonjour']);

    // Cleanup
    File::delete($csvPath);
});

it('can import translations as JSON', function () {
    $csvPath = base_path('import_test.csv');
    $content = "Path,Key,Original,New\n";
    $content .= "en,greeting,Old Hello,Hello\n";
    $content .= "en,nested.key,Old Value,Value\n";

    File::put($csvPath, $content);

    $this->artisan('translation:import', ['path' => $csvPath, '--json' => true])
        ->assertExitCode(0);

    $langPath = lang_path();
    $this->assertFileExists($langPath.'/en.json');

    $jsonContent = File::get($langPath.'/en.json');
    $data = json_decode($jsonContent, true);

    expect($data)->toBe([
        'greeting' => 'Hello',
        'nested' => [
            'key' => 'Value',
        ],
    ]);

    // Cleanup
    File::delete($csvPath);
});
