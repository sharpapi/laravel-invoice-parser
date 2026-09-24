---
name: sharpapi-invoice-parser
description: Extract structured invoice data (seller, buyer, line items, totals, tax, payment, e-invoice metadata) from invoice files with sharpapi/laravel-invoice-parser (InvoiceParserService::parseInvoice, fetchResults, SharpApiJob, config/sharpapi-invoice-parser.php). Use when parsing an uploaded or S3-stored invoice PDF/image, running the parse in a queued job, handling a failed or timed-out parse, or mocking the parser in tests.
---

# SharpAPI Invoice Parser

## When to use this skill

- Turning an invoice file into structured data: document type, invoice numbers and dates, seller and buyer, line items, financials and tax breakdown, payment and logistics details, e-invoice metadata.
- Writing the queued job that submits the file and waits for the result.
- Handling a failed parse, a polling timeout or a rate limit.
- Mocking the parser in feature tests.

Supported files (per the README): PDF, TIFF, JPG, PNG. The README states no file size limit.

## Install / config

1. `composer require sharpapi/laravel-invoice-parser`. The provider `SharpAPI\InvoiceParser\InvoiceParserProvider` is auto-discovered. Requires `sharpapi/php-core` ≥ 1.4.1 (pulled in automatically).
2. `.env`:
   ```dotenv
   SHARP_API_KEY=your-api-key
   # optional
   SHARP_API_BASE_URL=https://sharpapi.com/api/v1
   SHARP_API_JOB_STATUS_POLLING_WAIT=180          # max seconds fetchResults() blocks
   SHARP_API_JOB_STATUS_POLLING_INTERVAL=10       # seconds between polls...
   SHARP_API_JOB_STATUS_USE_POLLING_INTERVAL=false # ...only used when true; otherwise the API's Retry-After wins
   ```
3. Optional: `php artisan vendor:publish --tag=sharpapi-invoice-parser` publishes `config/sharpapi-invoice-parser.php` (keys `api_key`, `base_url`, `api_job_status_polling_wait`, `api_job_status_polling_interval`, `api_job_status_use_polling_interval`).

There is no facade and no container binding. `InvoiceParserService` has a no-argument constructor that reads the config, so inject it (preferred, mockable) or `new` it. A missing key throws `InvalidArgumentException` when the service is constructed.

## API

```php
use SharpAPI\InvoiceParser\InvoiceParserService;

public function parseInvoice(string $filePath): string // returns a status URL
```

- `$filePath`: a local, readable file path. The client reads it with `file_get_contents()` and uploads it as multipart, sending `basename($filePath)` as the filename. There is no language or other parameter.
- Returns the job's status URL, not the result. Get the result with `fetchResults($statusUrl)`.

`fetchResults(string $statusUrl): SharpAPI\Core\DTO\SharpApiJob` (inherited from `SharpAPI\Core\Client\SharpApiClient`) polls until the job is `success` or `failed`. The DTO has `id`, `type` (`'invoice_parse'` in the README example), `status` (string) and `result` (`?stdClass`), plus `getResultJson()`, `getResultArray()`, `getResultObject()`, `toArray()`. `quota()` and `ping()` are also inherited.

The result is a **list of documents**, one per invoice found in the file, each with the pages it came from. Shape from the README example, trimmed (every section has more fields; many are `""` or `null` when absent):

```json
[
  {
    "source_pages": [1],
    "document": { "type": "invoice", "original_type_label": "", "is_copy": false, "copy_type": null },
    "invoice": { "invoice_number": "D7BDFA00-0019", "issue_date": "2025-12-07", "due_date": "2025-12-07", "currency": "USD" },
    "references": { "purchase_order_number": "", "customer_reference": "", "other_references": [] },
    "e_invoice": { "uuid": "", "qr_code_present": false },
    "seller": { "name": "OpenAl, LLC", "vat_id": "GB434338990", "address": { "city": "San Francisco", "country": "US" }, "bank_details": [] },
    "buyer": { "name": "A2Z WEB LTD", "billing_address": { "city": "Rotherham", "postcode": "S63 5DB", "country": "GB" } },
    "sales_info": { "salesperson_name": "" },
    "financials": {
      "subtotal": 15.57, "total_excl_tax": 15.57, "total_tax_amount": 3.11, "total_incl_tax": 18.68,
      "total_payable": 18.68, "amount_due": 18.68,
      "tax_details": [{ "tax_type": "VAT", "tax_rate": 20, "taxable_amount": 15.57, "tax_amount": 3.11 }]
    },
    "line_items": [
      { "line_number": 1, "description": "OpenAl API usage credit", "quantity": 1, "unit_price": 15.57, "tax_rate": 20, "tax_amount": 3.11, "total_excl_tax": 15.57 }
    ],
    "payment": { "payment_terms": "", "payment_method": "", "payment_date": null },
    "logistics": { "shipping_method": "", "total_weight": null }
  }
]
```

## Recipe: queued job

```php
namespace App\Jobs;

use App\Models\InvoiceUpload;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use SharpAPI\Core\Enums\SharpApiJobStatusEnum;
use SharpAPI\InvoiceParser\InvoiceParserService;

class ParseInvoiceUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;              // a retry re-uploads the file and burns quota
    public int $timeout;
    public bool $failOnTimeout = true;

    public function __construct(public InvoiceUpload $upload)
    {
        // fetchResults() may block for the whole polling wait; add headroom for the upload.
        $this->timeout = (int) config('sharpapi-invoice-parser.api_job_status_polling_wait', 180) + 60;
    }

    public function handle(InvoiceParserService $parser): void
    {
        // The file lives on S3: copy it to a local temp path the worker can read.
        $tmp = sys_get_temp_dir().'/'.Str::uuid().'_'.basename($this->upload->path);
        file_put_contents($tmp, Storage::disk('s3')->get($this->upload->path));

        try {
            $statusUrl = $parser->parseInvoice($tmp);
        } finally {
            @unlink($tmp);
        }

        $job = $parser->fetchResults($statusUrl); // blocks until success or failed

        if ($job->status !== SharpApiJobStatusEnum::SUCCESS->value) {
            throw new RuntimeException("SharpAPI invoice parsing failed (job {$job->id}).");
        }

        $documents = json_decode($job->getResultJson(), true); // [0 => [...], 1 => [...]]

        foreach ($documents as $document) {
            $this->upload->invoices()->create([
                'number' => $document['invoice']['invoice_number'] ?? null,
                'currency' => $document['invoice']['currency'] ?? null,
                'total' => $document['financials']['total_payable'] ?? null,
                'data' => $document,
            ]);
        }
    }
}
```

- Keep the original extension in the temp file name: the basename is sent as the upload filename.
- Need retries? Save `$statusUrl` on the model right after `parseInvoice()`. On a retry, skip the upload and call only `fetchResults()` on the saved URL; it only reads the job status.
- The queue connection's `retry_after` must be greater than the job `$timeout`, and a Horizon supervisor's `timeout` at least as large, or the job runs twice.

## Gotchas

1. **`fetchResults()` already blocks and polls** (up to `api_job_status_polling_wait`, honouring the API's `Retry-After`). Call it once. Never wrap it in `while ($job->status === 'pending')`: it only returns on `success` or `failed`, and each extra call starts a new wait.
2. **A failed job does not throw.** It returns a `SharpApiJob` with `status === 'failed'` and no usable `result`. Compare `$job->status` with `SharpAPI\Core\Enums\SharpApiJobStatusEnum::SUCCESS->value` before reading the data, or you store an empty result as a parsed invoice.
3. **Never call `fetchResults()` in an HTTP request.** Use a queued job whose `$timeout` exceeds the polling wait, with low `$tries`.
4. **Use `json_decode($job->getResultJson(), true)`.** The result is a JSON list, which php-core casts to a `stdClass` with numeric property names (`"0"`, `"1"`), so `$job->result[0]` throws an `Error`. `getResultArray()` casts only that top level, and whether each document is then an array or a `stdClass` depends on the status URL form. `json_decode(..., true)` always gives a plain list of arrays.
5. **One file can hold several invoices.** Loop over every document; do not assume `[0]` is the only one.
6. **Local readable path only.** A Storage key, an S3 path or an `UploadedFile` does not work, and the request's temp upload is gone by the time a queued job runs. A missing file surfaces as an `ErrorException` from `file_get_contents()`.
7. Errors: a polling timeout throws `SharpAPI\Core\Exceptions\ApiException` ("Polling timed out ..."); a 429 on submit is tried 3 times in total, then throws `ApiException` with code 429. Other 4xx/5xx surface as Guzzle exceptions (`GuzzleHttp\Exception\ClientException` / `GuzzleException`).

## Testing

php-core sends requests with its own Guzzle client, so **`Http::fake()` does not intercept them**. Mock the service instead (works when the code resolves it from the container, e.g. `handle(InvoiceParserService $parser)`):

```php
use Mockery\MockInterface;
use SharpAPI\Core\DTO\SharpApiJob;
use SharpAPI\InvoiceParser\InvoiceParserService;

it('stores every parsed invoice', function () {
    $this->mock(InvoiceParserService::class, function (MockInterface $mock) {
        $mock->shouldReceive('parseInvoice')->once()->andReturn('https://sharpapi.com/api/v1/job/status/job-1');
        $mock->shouldReceive('fetchResults')->once()->andReturn(new SharpApiJob(
            id: 'job-1',
            type: 'invoice_parse',
            status: 'success',
            // php-core casts the list result the same way
            result: (object) [
                ['invoice' => ['invoice_number' => 'INV-1', 'currency' => 'GBP'], 'financials' => ['total_payable' => 120.0]],
            ],
        ));
    });

    // dispatch the job synchronously and assert on the models
});
```

Cover the failed path with `status: 'failed', result: new stdClass`. `$this->mock()` does not run the constructor, so tests need no API key; code that resolves the real service does (`config(['sharpapi-invoice-parser.api_key' => 'test-key'])`).
