<?php

namespace AdamPatterson\LaravelCsvTranslations\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \AdamPatterson\LaravelCsvTranslations\LaravelCsvTranslations
 */
class LaravelCsvTranslations extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \AdamPatterson\LaravelCsvTranslations\LaravelCsvTranslations::class;
    }
}
