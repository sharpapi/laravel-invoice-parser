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
        "id": "e00ebeec-6df3-4282-b515-8c447ea5d6ab",
        "attributes": {
            "status": "success",
            "type": "invoice_parse",
            "result": [
                {
                    "source_pages": [
                        1
                    ],
                    "document": {
                        "type": "invoice",
                        "original_type_label": "",
                        "is_copy": false,
                        "copy_type": null
                    },
                    "invoice": {
                        "invoice_number": "D7BDFA00-0019",
                        "issue_date": "2025-12-07",
                        "due_date": "2025-12-07",
                        "document_date": null,
                        "order_date": null,
                        "delivery_date": null,
                        "shipping_date": null,
                        "pricing_date": null,
                        "currency": "USD",
                        "exchange_rate": null,
                        "page_info": "1 of 1",
                        "amount_in_words": "",
                        "notes": "",
                        "remarks": "",
                        "delivery_instructions": "",
                        "terms_and_conditions": [],
                        "late_payment_interest_rate": null
                    },
                    "references": {
                        "delivery_order_number": "",
                        "purchase_order_number": "",
                        "sales_order_number": "",
                        "customer_reference": "",
                        "external_document_number": "",
                        "grn_number": "",
                        "route_number": "",
                        "lorry_number": "",
                        "serial_number": "",
                        "batch_number": "",
                        "other_references": []
                    },
                    "e_invoice": {
                        "uuid": "",
                        "e_invoice_code": "",
                        "e_invoice_type": "",
                        "e_invoice_version": "",
                        "submission_id": "",
                        "submission_document_id": "",
                        "submission_long_id": "",
                        "submission_status": "",
                        "validation_datetime": null,
                        "digital_signature_present": false,
                        "validated_link": "",
                        "original_e_invoice_ref": "",
                        "qr_code_present": false
                    },
                    "seller": {
                        "name": "OpenAl, LLC",
                        "trade_name": "OpenAl",
                        "registration_number": "",
                        "tin": "",
                        "sst_id": "",
                        "gst_id": "",
                        "vat_id": "GB434338990",
                        "msic_code": "",
                        "business_activity": "",
                        "address": {
                            "street_line_1": "548 Market Street",
                            "street_line_2": "PMB 97273",
                            "city": "San Francisco",
                            "state": "California",
                            "postcode": "94104-5401",
                            "country": "US"
                        },
                        "phone": "",
                        "fax": "",
                        "email": "",
                        "website": "",
                        "bank_details": [
                            {
                                "bank_name": "",
                                "account_name": "",
                                "account_number": "",
                                "sort_code": "",
                                "swift_code": "",
                                "iban": ""
                            }
                        ],
                        "contact_person": {
                            "name": "",
                            "role": "",
                            "phone": "",
                            "email": ""
                        }
                    },
                    "buyer": {
                        "name": "A2Z WEB LTD",
                        "trade_name": "",
                        "registration_number": "",
                        "tin": "",
                        "brn": "",
                        "sst_id": "",
                        "gst_id": "",
                        "vat_id": "",
                        "customer_account_number": "",
                        "billing_address": {
                            "location_name": "",
                            "street_line_1": "Unit 4e Enterprise Court, Farfield",
                            "street_line_2": "Park",
                            "city": "Rotherham",
                            "state": "",
                            "postcode": "S63 5DB",
                            "country": "GB"
                        },
                        "delivery_address": {
                            "recipient_name": "",
                            "location_name": "",
                            "street_line_1": "Unit 10 Enterprise Court",
                            "street_line_2": "Farfield Park",
                            "city": "Rotherham",
                            "state": "",
                            "postcode": "S63 5DB",
                            "country": "GB"
                        },
                        "delivery_address_same_as_billing": false,
                        "phone": "",
                        "fax": "",
                        "email": "",
                        "attention_to": {
                            "name": "",
                            "phone": "",
                            "email": ""
                        }
                    },
                    "sales_info": {
                        "salesperson_name": "",
                        "salesperson_code": "",
                        "salesperson_phone": "",
                        "sales_agent": "",
                        "sales_location": "",
                        "sales_department": "",
                        "outlet_name": ""
                    },
                    "financials": {
                        "subtotal": 15.57,
                        "gross_amount": null,
                        "total_discount_amount": null,
                        "shipping_charge": null,
                        "delivery_fee": null,
                        "total_excl_tax": 15.57,
                        "total_tax_amount": 3.11,
                        "service_tax_amount": null,
                        "total_incl_tax": 18.68,
                        "rounding_adjustment": null,
                        "total_payable": 18.68,
                        "amount_paid": null,
                        "amount_due": 18.68,
                        "tax_details": [
                            {
                                "tax_type": "VAT",
                                "tax_rate": 20,
                                "taxable_amount": 15.57,
                                "tax_amount": 3.11
                            }
                        ]
                    },
                    "line_items": [
                        {
                            "line_number": 1,
                            "item_code": "",
                            "stock_code": "",
                            "barcode": "",
                            "description": "OpenAl API usage credit",
                            "classification_code": "",
                            "country_of_origin": "",
                            "quantity": 1,
                            "free_quantity": null,
                            "unit_of_measure": "",
                            "unit_of_measure_raw": "",
                            "pack_size": "",
                            "total_units": null,
                            "weight": null,
                            "weight_uom": "",
                            "unit_price": 15.57,
                            "discount_percent": null,
                            "discount_amount": null,
                            "subtotal": 15.57,
                            "tax_rate": 20,
                            "tax_type": "VAT",
                            "tax_amount": 3.11,
                            "total_excl_tax": 15.57,
                            "total_incl_tax": null,
                            "expiry_date": null,
                            "batch_lot_number": "",
                            "service_start_date": null,
                            "service_end_date": null
                        }
                    ],
                    "payment": {
                        "payment_terms": "",
                        "payment_terms_days": null,
                        "payment_method": "",
                        "payment_date": null,
                        "payment_reference": "",
                        "jompay_biller_code": "",
                        "jompay_ref_1": ""
                    },
                    "logistics": {
                        "shipping_method": "",
                        "vehicle_number": "",
                        "driver_name": "",
                        "delivery_zone": "",
                        "delivery_time_constraint": "",
                        "carton_count": null,
                        "total_volume": null,
                        "total_weight": null,
                        "goods_received_confirmation": false,
                        "received_by": "",
                        "receiver_signature_present": false
                    }
                }
            ]
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
