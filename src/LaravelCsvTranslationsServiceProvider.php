<?php

namespace AdamPatterson\LaravelCsvTranslations;

use AdamPatterson\LaravelCsvTranslations\Commands\ImportFromCsvCommand;
use AdamPatterson\LaravelCsvTranslations\Commands\ExportToCsvCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelCsvTranslationsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-csv-translations')
            ->hasConfigFile()
            ->hasCommand(ExportToCsvCommand::class)
            ->hasCommand(ImportFromCsvCommand::class);
    }
}
