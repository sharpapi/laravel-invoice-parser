<?php

declare(strict_types=1);

namespace SharpAPI\InvoiceParser\Tests;

use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as Orchestra;
use SharpAPI\InvoiceParser\InvoiceParserProvider;

abstract class TestCase extends Orchestra
{
    /**
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [InvoiceParserProvider::class];
    }

    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('sharpapi-invoice-parser.api_key', 'test-key');
    }
}
