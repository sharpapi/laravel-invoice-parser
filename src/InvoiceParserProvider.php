<?php

declare(strict_types=1);

namespace SharpAPI\InvoiceParser;

use Illuminate\Support\ServiceProvider;

/**
 * @api
 */
class InvoiceParserProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/sharpapi-invoice-parser.php' => config_path('sharpapi-invoice-parser.php'),
            ], 'sharpapi-invoice-parser');
        }
    }

    /**
     * Register the application services.
     */
    public function register(): void
    {
        // Merge the package configuration with the app configuration.
        $this->mergeConfigFrom(
            __DIR__.'/../config/sharpapi-invoice-parser.php', 'sharpapi-invoice-parser'
        );
    }
}
