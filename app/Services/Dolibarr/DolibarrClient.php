<?php

namespace App\Services\Dolibarr;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class DolibarrClient
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.dolibarr.base_url'), '/');
    }

    public function searchCustomers(string $term, int $limit = 15): array
    {
        $term = trim($term);
        $query = [
            'sortfield' => 't.nom',
            'sortorder' => 'ASC',
            'limit' => $limit,
            'mode' => 1,
        ];

        if ($term !== '') {
            $query['sqlfilters'] = "(t.nom:like:'%".$this->escapeFilter($term)."%')";
        }

        return collect($this->request('get', '/thirdparties', $query))
            ->map(fn (array $customer): array => [
                'id' => (int) ($customer['id'] ?? $customer['rowid'] ?? 0),
                'name' => (string) ($customer['name'] ?? $customer['nom'] ?? ''),
                'code_client' => $customer['code_client'] ?? null,
                'town' => $customer['town'] ?? null,
                'zip' => $customer['zip'] ?? null,
            ])
            ->filter(fn (array $customer): bool => $customer['id'] > 0 && $customer['name'] !== '')
            ->values()
            ->all();
    }

    public function createCustomer(array $payload): array
    {
        $id = $this->extractId($this->request('post', '/thirdparties', [
            'name' => $payload['name'],
            'client' => 1,
            'email' => $payload['email'] ?? null,
            'phone' => $payload['phone'] ?? null,
            'address' => $payload['address'] ?? null,
            'zip' => $payload['zip'] ?? null,
            'town' => $payload['town'] ?? null,
            'country_id' => $payload['country_id'] ?? null,
        ]));

        return $this->getCustomer($id);
    }

    public function getCustomer(int $id): array
    {
        $customer = $this->request('get', '/thirdparties/'.$id);

        return [
            'id' => (int) ($customer['id'] ?? $customer['rowid'] ?? $id),
            'name' => (string) ($customer['name'] ?? $customer['nom'] ?? ''),
            'code_client' => $customer['code_client'] ?? null,
            'address' => $customer['address'] ?? null,
            'town' => $customer['town'] ?? null,
            'zip' => $customer['zip'] ?? null,
            'state' => $customer['state'] ?? $customer['state_code'] ?? null,
            'country' => $customer['country'] ?? $customer['country_code'] ?? null,
            'email' => $customer['email'] ?? null,
            'phone' => $customer['phone'] ?? null,
            'phone_mobile' => $customer['phone_mobile'] ?? null,
            'url' => $customer['url'] ?? null,
            'vat_number' => $customer['tva_intra'] ?? null,
        ];
    }

    public function searchMachineProducts(?string $manufacturer, ?string $ref, int $limit = 100): array
    {
        $baseQuery = [
            'sortfield' => 't.ref',
            'sortorder' => 'ASC',
        ];

        $ref = trim((string) $ref);
        if ($ref !== '') {
            $baseQuery['sqlfilters'] = "(t.ref:like:'%".$this->escapeFilter($ref)."%')";
        }

        $manufacturer = trim((string) $manufacturer);

        return collect($this->fetchProducts($baseQuery, 250, 20))
            ->map(fn (array $product): array => $this->mapProduct($product))
            ->filter(function (array $product) use ($manufacturer): bool {
                if ($manufacturer === '') {
                    return true;
                }

                $productManufacturer = $product['manufacturer'] ?? '';

                // Include if manufacturer matches OR if product has no manufacturer set
                // (ref-only matches are never hidden due to missing Dolibarr metadata)
                return $productManufacturer === ''
                    || strcasecmp($productManufacturer, $manufacturer) === 0;
            })
            ->take($limit)
            ->values()
            ->all();
    }

    public function listManufacturers(int $pageSize = 250, int $maxPages = 40): array
    {
        return collect($this->fetchProducts([
            'sortfield' => 't.ref',
            'sortorder' => 'ASC',
        ], $pageSize, $maxPages))
            ->map(fn (array $product): array => $this->mapProduct($product))
            ->pluck('manufacturer')
            ->map(fn (?string $manufacturer): string => trim((string) $manufacturer))
            ->filter()
            ->unique(fn (string $manufacturer): string => mb_strtolower($manufacturer))
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    public function createMachineProduct(array $payload): array
    {
        $id = $this->extractId($this->request('post', '/products', [
            'ref' => $payload['ref'],
            'label' => $payload['label'] ?? $payload['ref'],
            'type' => 0,
            'status' => 1,
            'status_buy' => 0,
            'array_options' => [
                'options_hersteller' => $payload['manufacturer'] ?? null,
            ],
        ]));

        return $this->getProduct($id);
    }

    public function findProductByRef(string $ref): ?array
    {
        $products = $this->request('get', '/products', [
            'limit' => 5,
            'sqlfilters' => "(t.ref:like:'".$this->escapeFilter($ref)."')",
        ]);

        foreach ($products as $product) {
            $mapped = $this->mapProduct($product);
            if (strcasecmp($mapped['ref'], $ref) === 0) {
                return $mapped;
            }
        }

        return null;
    }

    public function getProduct(int $id): array
    {
        return $this->mapProduct($this->request('get', '/products/'.$id));
    }

    public function createDraftOrder(int $customerId, array $context = []): array
    {
        $payload = [
            'socid' => $customerId,
            'date' => now()->timestamp,
            'note_private' => trim((string) ($context['note_private'] ?? '')),
        ];

        $id = $this->extractId($this->request('post', '/orders', $payload));
        $order = $this->request('get', '/orders/'.$id);

        return [
            'id' => (int) ($order['id'] ?? $order['rowid'] ?? $id),
            'ref' => (string) ($order['ref'] ?? $order['ref_client'] ?? $id),
        ];
    }

    public function addProductOrderLine(int $orderId, array $line): int
    {
        $payload = [
            'fk_product' => (int) $line['product_id'],
            'qty' => (float) $line['quantity'],
            'subprice' => (float) ($line['unit_price'] ?? 0),
            'tva_tx' => (float) ($line['vat_rate'] ?? 19),
            'desc' => $line['description'] ?? null,
            'price_base_type' => 'HT',
            'product_type' => (int) ($line['product_type'] ?? 1),
        ];

        return $this->extractId($this->request('post', '/orders/'.$orderId.'/lines', $payload));
    }

    public function addFreeOrderLine(int $orderId, array $line): int
    {
        $payload = [
            'desc' => $line['description'],
            'qty' => (float) $line['quantity'],
            'subprice' => (float) $line['unit_price'],
            'tva_tx' => (float) $line['vat_rate'],
            'price_base_type' => 'HT',
            'product_type' => 0,
        ];

        return $this->extractId($this->request('post', '/orders/'.$orderId.'/lines', $payload));
    }

    public function addProductInvoiceLine(int $invoiceId, array $line): int
    {
        $payload = [
            'fk_product' => (int) $line['product_id'],
            'qty' => (float) $line['quantity'],
            'subprice' => (float) ($line['unit_price'] ?? 0),
            'tva_tx' => (float) ($line['vat_rate'] ?? 19),
            'desc' => $line['description'] ?? null,
            'price_base_type' => 'HT',
            'product_type' => (int) ($line['product_type'] ?? 1),
        ];

        return $this->extractId($this->request('post', '/invoices/'.$invoiceId.'/lines', $payload));
    }

    public function addFreeInvoiceLine(int $invoiceId, array $line): int
    {
        $payload = [
            'desc' => $line['description'],
            'qty' => (float) $line['quantity'],
            'subprice' => (float) $line['unit_price'],
            'tva_tx' => (float) $line['vat_rate'],
            'price_base_type' => 'HT',
            'product_type' => 0,
        ];

        return $this->extractId($this->request('post', '/invoices/'.$invoiceId.'/lines', $payload));
    }

    public function validateOrder(int $orderId): void
    {
        $this->request('post', '/orders/'.$orderId.'/validate', []);
    }

    public function closeOrder(int $orderId): void
    {
        $this->request('post', '/orders/'.$orderId.'/close', ['notrigger' => 0]);
    }

    public function createInvoiceFromOrder(int $orderId, int $customerId, array $lines = []): array
    {
        $payload = [
            'socid' => $customerId,
            'date' => now()->timestamp,
            'lines' => $lines,
        ];

        $id = $this->extractId($this->request('post', '/invoices', $payload));
        $invoice = $this->request('get', '/invoices/'.$id);

        return [
            'id' => (int) ($invoice['id'] ?? $invoice['rowid'] ?? $id),
            'ref' => (string) ($invoice['ref'] ?? $id),
        ];
    }

    public function validateInvoice(int $invoiceId): void
    {
        $this->request('post', '/invoices/'.$invoiceId.'/validate', []);
    }

    private function http(): PendingRequest
    {
        if ($this->baseUrl === '' || blank(config('services.dolibarr.api_key'))) {
            throw new RuntimeException('Dolibarr ist nicht konfiguriert. Bitte DOLIBARR_BASE_URL und DOLIBARR_API_KEY setzen.');
        }

        return Http::baseUrl($this->baseUrl)
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('services.dolibarr.timeout', 20))
            ->withHeaders([
                'DOLAPIKEY' => (string) config('services.dolibarr.api_key'),
            ]);
    }

    private function request(string $method, string $uri, array $payload = []): mixed
    {
        $response = match ($method) {
            'get' => $this->http()->get($uri, $payload),
            'post' => $this->http()->post($uri, Arr::whereNotNull($payload)),
            'put' => $this->http()->put($uri, Arr::whereNotNull($payload)),
            'delete' => $this->http()->delete($uri),
            default => throw new RuntimeException('Nicht unterstuetzte Dolibarr-Methode: '.$method),
        };
        if ($response->status() === 404 && $method === 'get' && in_array($uri, ['/thirdparties', '/products'], true)) {
            return [];
        }

        if ($response->failed()) {
            throw new RuntimeException('Dolibarr API Fehler '.$response->status().' bei '.strtoupper($method).' '.$uri.': '.$response->body());
        }

        return $response->json() ?? $response->body();
    }

    private function fetchProducts(array $baseQuery, int $pageSize, int $maxPages): array
    {
        $products = [];

        for ($page = 0; $page < $maxPages; $page++) {
            $batch = $this->request('get', '/products', $baseQuery + [
                'limit' => $pageSize,
                'page' => $page,
            ]);

            if (! is_array($batch) || $batch === []) {
                break;
            }

            $products = array_merge($products, $batch);

            if (count($batch) < $pageSize) {
                break;
            }
        }

        return $products;
    }

    private function extractId(mixed $response): int
    {
        if (is_numeric($response)) {
            return (int) $response;
        }

        if (is_array($response)) {
            foreach (['id', 'rowid', 'lineid'] as $key) {
                if (isset($response[$key]) && is_numeric($response[$key])) {
                    return (int) $response[$key];
                }
            }
        }

        throw new RuntimeException('Dolibarr-Antwort enthaelt keine ID.');
    }

    private function mapProduct(array $product): array
    {
        $options = $product['array_options'] ?? [];

        return [
            'id' => (int) ($product['id'] ?? $product['rowid'] ?? 0),
            'ref' => (string) ($product['ref'] ?? ''),
            'label' => (string) ($product['label'] ?? ''),
            'manufacturer' => trim((string) ($options['options_hersteller'] ?? $options['hersteller'] ?? '')),
            'price' => isset($product['price']) ? (float) $product['price'] : null,
            'vat_rate' => isset($product['tva_tx']) ? (float) $product['tva_tx'] : 19.0,
            'type' => (int) ($product['type'] ?? 0),
        ];
    }

    private function escapeFilter(string $value): string
    {
        return str_replace(["\\", "'"], ["\\\\", "\\'"], $value);
    }
}
