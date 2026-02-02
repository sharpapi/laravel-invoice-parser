<?php

declare(strict_types=1);

namespace SharpAPI\InvoiceParser;

use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;
use SharpAPI\Core\Client\SharpApiClient;

/**
 * @api
 */
class InvoiceParserService extends SharpApiClient
{
    /**
     * Initializes a new instance of the class.
     *
     * @throws InvalidArgumentException if the API key is empty.
     */
    public function __construct()
    {
        parent::__construct(config('sharpapi-invoice-parser.api_key'));
        $this->setApiBaseUrl(
            config(
                'sharpapi-invoice-parser.base_url',
                'https://sharpapi.com/api/v1'
            )
        );
        $this->setApiJobStatusPollingInterval(
            (int) config(
                'sharpapi-invoice-parser.api_job_status_polling_interval',
                5)
        );
        $this->setApiJobStatusPollingWait(
            (int) config(
                'sharpapi-invoice-parser.api_job_status_polling_wait',
                180)
        );
        $this->setUserAgent('SharpAPILaravelInvoiceParser/1.0.0');

    }

    /**
     * Parses an invoice file from multiple formats (PDF/TIFF/JPG/PNG)
     * and returns an extensive JSON object of data points.
     *
     * @param  string  $filePath  The path to the invoice file.
     * @return string The parsed data or an error message.
     *
     * @throws GuzzleException
     *
     * @api
     */
    public function parseInvoice(
        string $filePath,
    ): string {
        $response = $this->makeRequest(
            'POST',
            '/finance/parse_invoice',
            [],
            $filePath
        );

        return $this->parseStatusUrl($response);
    }
}
