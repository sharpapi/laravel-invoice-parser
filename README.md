![SharpAPI GitHub cover](https://sharpapi.com/sharpapi-github-laravel-bg.jpg "SharpAPI Laravel Client")

# Invoice Parser for Laravel with AI-powered SharpAPI

## 🚀 Leverage AI API to streamline invoice parsing and data extraction in your Accounting & Finance applications.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/sharpapi/laravel-invoice-parser.svg?style=flat-square)](https://packagist.org/packages/sharpapi/laravel-invoice-parser)
[![Total Downloads](https://img.shields.io/packagist/dt/sharpapi/laravel-invoice-parser.svg?style=flat-square)](https://packagist.org/packages/sharpapi/laravel-invoice-parser)

Check the details at SharpAPI's [Invoice Parser](https://sharpapi.com/en/catalog/ai/accounting-finance/invoice-parser) page.

---

## Requirements

- PHP >= 8.1
- Laravel >= 9.0

---

## Installation

Follow these steps to install and set up the SharpAPI Laravel Invoice Parser package.

1. Install the package via `composer`:

```bash
composer require sharpapi/laravel-invoice-parser
```

2. Register at [SharpAPI.com](https://sharpapi.com/) to obtain your API key.

3. Set the API key in your `.env` file:

```bash
SHARP_API_KEY=your_api_key_here
```

4. **[OPTIONAL]** Publish the configuration file:

```bash
php artisan vendor:publish --tag=sharpapi-invoice-parser
```

---
## Key Features

- **Automated Invoice Parsing with AI**: Efficiently parse and extract structured information from invoices in various formats, including PDF, TIFF, JPG, and PNG.
- **Comprehensive Data Extraction**: Extracts seller/buyer details, line items, financials, payment information, logistics data, and e-invoice metadata.
- **Consistent Data Format**: Ensures predictable JSON structure for parsed data.
- **Robust Polling for Results**: Polling-based API response handling with customizable intervals.
- **API Availability and Quota Check**: Check API availability and current usage quotas with `ping` and `quota` endpoints.

---

## Usage

You can inject the `InvoiceParserService` class to access parsing functionalities. For best results, especially with batch processing, use Laravel's queuing system to optimize job dispatch and result polling.

### Basic Workflow

1. **Dispatch Job**: Send an invoice file to the API using `parseInvoice`, which returns a status URL.
2. **Poll for Results**: Use `fetchResults($statusUrl)` to poll until the job completes or fails.
3. **Process Result**: After completion, retrieve the results from the `SharpApiJob` object returned.

> **Note**: Each job typically takes a few seconds to complete. Once completed successfully, the status will update to `success`, and you can process the results as JSON, array, or object format.

---

### Controller Example

Here is an example of how to use `InvoiceParserService` within a Laravel controller:

```php
<?php

namespace App\Http\Controllers;

use GuzzleHttp\Exception\GuzzleException;
use SharpAPI\InvoiceParser\InvoiceParserService;

class InvoiceController extends Controller
{
    protected InvoiceParserService $invoiceParserService;

    public function __construct(InvoiceParserService $invoiceParserService)
    {
        $this->invoiceParserService = $invoiceParserService;
    }

    /**
     * @throws GuzzleException
     */
    public function parseInvoice()
    {
        $statusUrl = $this->invoiceParserService->parseInvoice(
            '/path/to/invoice.pdf'
        );
        $result = $this->invoiceParserService->fetchResults($statusUrl);

        return response()->json($result->getResultJson());
    }
}
```

### Handling Guzzle Exceptions

All requests are managed by Guzzle, so it's helpful to be familiar with [Guzzle Exceptions](https://docs.guzzlephp.org/en/stable/quickstart.html#exceptions).

Example:

```php
use GuzzleHttp\Exception\ClientException;

try {
    $statusUrl = $this->invoiceParserService->parseInvoice('/path/to/invoice.pdf');
} catch (ClientException $e) {
    echo $e->getMessage();
}
```

---

## Optonal Configuration

You can customize the configuration by setting the following environment variables in your `.env` file:

```bash
SHARP_API_KEY=your_api_key_here
SHARP_API_JOB_STATUS_POLLING_WAIT=180
SHARP_API_JOB_STATUS_USE_POLLING_INTERVAL=true
SHARP_API_JOB_STATUS_POLLING_INTERVAL=10
SHARP_API_BASE_URL=https://sharpapi.com/api/v1
```

---

### Available Endpoints

#### Invoice Parsing

Parses an invoice in multiple formats (PDF, TIFF, JPG, PNG) and returns structured data points.

```php
$statusUrl = $invoiceParserService->parseInvoice('/path/to/invoice.pdf');
```

#### Quota Check

Returns information about the subscription, including usage and remaining quota.

```php
$quotaInfo = $invoiceParserService->quota();
```

#### API Lightweight Availability Check (Ping)

Checks the API availability and server timestamp.

```php
$pingResponse = $invoiceParserService->ping();
```

---

## AI Invoice Parsing Data Format Example

```json
{
  "data": {
    "type": "api_job_result",
    "id": "d42b5e66-baa0-4541-bc4a-94060d643ffe",
    "attributes": {
      "status": "success",
      "type": "invoice_parse",
      "result": {
        "document": {
          "type": "invoice",
          "date": "2024-01-15",
          "number": "INV-2024-001",
          "currency": "USD"
        },
        "invoice": {
          "issue_date": "2024-01-15",
          "due_date": "2024-02-15",
          "purchase_order": "PO-12345"
        },
        "references": {
          "order_number": "ORD-2024-001",
          "contract_number": null
        },
        "e_invoice": {
          "format": null,
          "version": null
        },
        "seller": {
          "name": "Acme Corp",
          "address": "123 Business Ave, Suite 100, New York, NY 10001",
          "tax_id": "US12-3456789",
          "email": "billing@acmecorp.com",
          "phone": "+1-555-123-4567"
        },
        "buyer": {
          "name": "Widget Industries Ltd",
          "address": "456 Commerce St, Chicago, IL 60601",
          "tax_id": "US98-7654321",
          "email": "accounts@widgetind.com",
          "phone": "+1-555-987-6543"
        },
        "financials": {
          "subtotal": 2500.00,
          "tax_rate": 8.5,
          "tax_amount": 212.50,
          "discount": 0.00,
          "total": 2712.50
        },
        "line_items": [
          {
            "description": "Professional consulting services",
            "quantity": 10,
            "unit": "hours",
            "unit_price": 150.00,
            "amount": 1500.00,
            "tax_rate": 8.5
          },
          {
            "description": "Software license - annual subscription",
            "quantity": 1,
            "unit": "license",
            "unit_price": 1000.00,
            "amount": 1000.00,
            "tax_rate": 8.5
          }
        ],
        "payment": {
          "method": "bank_transfer",
          "bank_name": "First National Bank",
          "account_number": "****4567",
          "routing_number": "021000021",
          "terms": "Net 30"
        },
        "logistics": {
          "shipping_address": null,
          "delivery_date": null,
          "tracking_number": null
        }
      }
    }
  }
}
```

---

## Support & Feedback

For issues or suggestions, please:

- [Open an issue on GitHub](https://github.com/sharpapi/laravel-invoice-parser/issues)
- Join our [Telegram community](https://t.me/sharpapi_community)

---

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for a detailed list of changes.

---

## Credits

- [A2Z WEB LTD](https://github.com/a2zwebltd)
- [Dawid Makowski](https://github.com/makowskid)
- Enhance your [Laravel AI](https://sharpapi.com/) capabilities!

---

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

---

## Follow Us

Stay updated with news, tutorials, and case studies:

- [SharpAPI on X (Twitter)](https://x.com/SharpAPI)
- [SharpAPI on YouTube](https://www.youtube.com/@SharpAPI)
- [SharpAPI on Vimeo](https://vimeo.com/SharpAPI)
- [SharpAPI on LinkedIn](https://www.linkedin.com/products/a2z-web-ltd-sharpapicom-automate-with-aipowered-api/)
- [SharpAPI on Facebook](https://www.facebook.com/profile.php?id=61554115896974)
