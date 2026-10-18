<?php
/**
 * ============================================================================
 * Request examples for companies: shown as curl on /v7/panel/keys.php and
 * downloadable as a Postman collection (/v7/panel/postman.php).
 * ============================================================================
 *
 * The examples never contain a real key: curl reads it from $PEVIITOR_API_KEY
 * and Postman from the {{apiKey}} variable, so a key does not end up in copied
 * snippets, shell history or a downloaded file.
 * ============================================================================
 */

const EXAMPLE_JOB_URL = 'https://firma-ta.ro/cariere/java-developer';

/**
 * @return array<int, array{name: string, method: string, query: ?array, body: ?array}>
 */
function companyApiExamples(): array {
    return [
        [
            'name'   => 'Listează joburile firmei',
            'method' => 'GET',
            'query'  => null,
            'body'   => null,
        ],
        [
            'name'   => 'Adaugă un job',
            'method' => 'POST',
            'query'  => null,
            'body'   => [
                'url'      => EXAMPLE_JOB_URL,
                'title'    => 'Java Developer',
                'location' => ['Cluj-Napoca'],
                'workmode' => 'hybrid',
                'tags'     => ['java', 'spring'],
            ],
        ],
        [
            'name'   => 'Modifică un job',
            'method' => 'PUT',
            'query'  => null,
            'body'   => [
                'url'   => EXAMPLE_JOB_URL,
                'title' => 'Senior Java Developer',
            ],
        ],
        [
            'name'   => 'Șterge un job',
            'method' => 'DELETE',
            'query'  => null,
            // The url can also go in the query string: DELETE /v7/jobs/?url=<url-encoded>
            'body'   => ['url' => EXAMPLE_JOB_URL],
        ],
    ];
}

function exampleJson(array $body, int $flags = 0): string {
    return json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | $flags);
}

/**
 * The example as a curl command (bash), reading the key from $PEVIITOR_API_KEY.
 */
function exampleCurl(array $example, string $jobsUrl): string {
    $url = $jobsUrl . ($example['query'] ? '?' . http_build_query($example['query'], '', '&', PHP_QUERY_RFC3986) : '');
    $lines = [($example['method'] === 'GET' ? 'curl' : "curl -X {$example['method']}") . " \"$url\"", '  -H "X-Api-Key: $PEVIITOR_API_KEY"'];
    if ($example['body'] !== null) {
        $lines[] = '  -H "Content-Type: application/json"';
        // Single quotes for bash; the example bodies contain no single quote
        $lines[] = "  -d '" . exampleJson($example['body']) . "'";
    }
    return implode(" \\\n", $lines);
}

/**
 * Minimal Postman collection (v2.1) with the examples, no tests. The key goes in
 * the collection variable apiKey, sent as X-Api-Key by the collection-level auth.
 */
function examplesPostmanCollection(string $jobsUrl, string $company): array {
    $items = [];
    foreach (companyApiExamples() as $ex) {
        $url = ['raw' => '{{baseUrl}}', 'host' => ['{{baseUrl}}']];
        if ($ex['query']) {
            $url['raw'] .= '?' . http_build_query($ex['query'], '', '&', PHP_QUERY_RFC3986);
            $url['query'] = array_map(fn($k, $v) => ['key' => $k, 'value' => $v], array_keys($ex['query']), $ex['query']);
        }
        $request = ['method' => $ex['method'], 'header' => [], 'url' => $url];
        if ($ex['body'] !== null) {
            $request['header'][] = ['key' => 'Content-Type', 'value' => 'application/json'];
            $request['body'] = ['mode' => 'raw', 'raw' => exampleJson($ex['body'], JSON_PRETTY_PRINT), 'options' => ['raw' => ['language' => 'json']]];
        }
        $items[] = ['name' => $ex['name'], 'request' => $request];
    }
    return [
        'info' => [
            'name'        => 'peviitor — joburi' . ($company !== '' ? " ($company)" : ''),
            'description' => "Publică și gestionează joburile firmei pe peviitor.ro.\n\n"
                           . "Pune cheia API în variabila de colecție `apiKey` (tab-ul Variables). "
                           . "Nu salva colecția cu cheia în ea într-un loc public.",
            'schema'      => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
        ],
        'auth' => [
            'type'   => 'apikey',
            'apikey' => [
                ['key' => 'key', 'value' => 'X-Api-Key', 'type' => 'string'],
                ['key' => 'value', 'value' => '{{apiKey}}', 'type' => 'string'],
                ['key' => 'in', 'value' => 'header', 'type' => 'string'],
            ],
        ],
        'item'     => $items,
        'variable' => [
            ['key' => 'baseUrl', 'value' => $jobsUrl],
            ['key' => 'apiKey', 'value' => ''],
        ],
    ];
}
