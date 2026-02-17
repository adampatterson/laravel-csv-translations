# Laravel CSV Translations

[![Latest Version on Packagist](https://img.shields.io/packagist/v/adampatterson/laravel-csv-translations.svg?style=flat-square)](https://packagist.org/packages/adampatterson/laravel-csv-translations)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/adampatterson/laravel-csv-translations/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/adampatterson/laravel-csv-translations/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/adampatterson/laravel-csv-translations/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/adampatterson/laravel-csv-translations/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/adampatterson/laravel-csv-translations.svg?style=flat-square)](https://packagist.org/packages/adampatterson/laravel-csv-translations)

This package allows you to export and import Laravel translations using CSV files. This makes it easy to share translations with non-technical team members or external
translation services.

It supports both standard Laravel language files and vendor-published translations, and can handle both PHP array and JSON formats.

## Installation

You can install the package via composer:

```bash
composer require adampatterson/laravel-csv-translations
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="laravel-csv-translations"
```

This is the contents of the published config file:

```php
return [
    /*
    |--------------------------------------------------------------------------
    | Default Export Path
    |--------------------------------------------------------------------------
    |
    | This is the default path where the translations will be exported to.
    |
    */
    'export_path' => base_path('lang.csv'),
];
```

## Usage

### Publishing Translations

By default, the Laravel application skeleton does not include the lang directory. If you would like to customize Laravel's language files, you may publish them via the
`lang:publish` Artisan command.

Further, you can also publish translations from any vendor package that has published its translations. To do this, use the `--vendor` (or `-v`) option with the package name:

```bash
php artisan lang:publish --vendor=vendor/package
```

For example, Filament's translations can be published with:

```bash
php artisan vendor:publish --tag=filament-translations
```

### Exporting Translations

To export your translations to a CSV file, use the `translation:export` command:

```bash
php artisan translation:export
```

By default, this exports the base locale (defined in `config('app.locale')`) to the path specified in your config.

#### Options

- **Specify Path**: Provide a path as an argument to change the output location.
  ```bash
  php artisan translation:export custom/path/translations.csv
  ```
- **Export All Locales**: Use the `--all` (or `-a`) flag to export every locale found in your `lang` directory.
  ```bash
  php artisan translation:export --all
  ```
- **Specific Locales**: Use the `--locales` (or `-l`) option with a comma-separated list.
  ```bash
  php artisan translation:export --locales=en,fr,es
  ```

The CSV will contain the following columns:

1. **Path**: The relative path to the translation file (e.g., `en/auth` or `vendor/package/en/messages`).
2. **Key**: The dot-notation key for the translation.
3. **Original**: The current translation value.
4. **New**: An empty column for you to provide new translations.

### Importing Translations

To import translations from a CSV file, use the `translation:import` command:

```bash
php artisan translation:import
```

The command will read the CSV and update your language files. If a value is present in the **New** column, it will be used; otherwise, the **Original** value is preserved.

#### Options

- **Specify Path**: Provide the path to the CSV file as an argument.
  ```bash
  php artisan translation:import custom/path/translations.csv
  ```
- **Filter by Locale**: Import only a specific locale from the CSV.
  ```bash
  php artisan translation:import --locale=fr
  ```
- **Import as JSON**: Convert the translations into JSON files instead of PHP arrays.
  ```bash
  php artisan translation:import --json
  ```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

- [Adam Patterson](https://github.com/adampatterson)
- [All Contributors](https://github.com/adampatterson/laravel-csv-translations/graphs/contributors)
