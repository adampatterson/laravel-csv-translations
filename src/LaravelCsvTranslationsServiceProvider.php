<?php

namespace AdamPatterson\LaravelCsvTranslations;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use AdamPatterson\LaravelCsvTranslations\Commands\ExportToCsvCommand;
use AdamPatterson\LaravelCsvTranslations\Commands\ImportFromCsvCommand;

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
