<?php

declare(strict_types=1);

use SharpAPI\InvoiceParser\InvoiceParserService;

it('reads the polling settings from config', function () {
    config([
        'sharpapi-invoice-parser.api_job_status_polling_wait' => 240,
        'sharpapi-invoice-parser.api_job_status_polling_interval' => 7,
    ]);

    $service = new InvoiceParserService;

    expect($service->getApiJobStatusPollingWait())->toBe(240)
        ->and($service->getApiJobStatusPollingInterval())->toBe(7);
});

it('leaves the custom polling interval off by default', function () {
    expect((new InvoiceParserService)->isUseCustomInterval())->toBeFalse();
});

it('applies the use-polling-interval flag from config', function () {
    config(['sharpapi-invoice-parser.api_job_status_use_polling_interval' => true]);

    expect((new InvoiceParserService)->isUseCustomInterval())->toBeTrue();
});

it('throws a clear exception when the API key is missing', function () {
    config(['sharpapi-invoice-parser.api_key' => null]);

    new InvoiceParserService;
})->throws(InvalidArgumentException::class, 'SHARP_API_KEY');
