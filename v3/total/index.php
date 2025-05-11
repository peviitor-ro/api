<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');

class EndpointNotAvailableException extends Exception {}

// Build query string
function buildQueryString(array $params): string
{
    return '?' . http_build_query($params);
}

// Fetch JSON with optional context (used for Solr auth)
function fetchJsonData(string $url, $context = null): array
{
    $headers = @get_headers($url);
    if ($headers === false || strpos($headers[0], '200') === false) {
        throw new EndpointNotAvailableException('Endpoint-ul nu este disponibil: ' . $url);
    }

    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        throw new EndpointNotAvailableException('Nu s-a putut obține conținutul de la: ' . $url);
    }

    $json = json_decode($response, true);
    if ($json === null) {
        throw new EndpointNotAvailableException('Răspuns JSON invalid de la: ' . $url);
    }

    return $json;
}

// Ensure GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["error" => "Only GET method is allowed"]);
    exit;
}

// Load .env variables
function loadEnv($file)
{
    $file = realpath($file);
    if (!$file || !file_exists($file)) {
        die(json_encode(["error" => "The api.env file does not exist!", "path" => $file]));
    }

    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($key, $value) = explode('=', $line, 2) + [NULL, NULL];
        if ($key && $value) {
            $key = trim($key);
            $value = trim($value);
            $_SERVER[$key] = $value;
            putenv("$key=$value");
        }
    }
}

try {
    loadEnv('../../api.env');

    $server = getenv('PROD_SERVER') ?: ($_SERVER['PROD_SERVER'] ?? null);
    $username = getenv('SOLR_USER') ?: ($_SERVER['SOLR_USER'] ?? null);
    $password = getenv('SOLR_PASS') ?: ($_SERVER['SOLR_PASS'] ?? null);

    if (!$server) {
        die(json_encode(["error" => "PROD_SERVER is not set in api.env"]));
    }

    $core = "jobs";
    $qs = http_build_query([
        "facet.field" => "company_str",
        "facet.limit" => "2000000",
        "facet" => "true",
        "fl" => "company",
        "indent" => "true",
        "q.op" => "OR",
        "q" => "*:*",
        "rows" => "0",
        "start" => "0",
        "useParams" => ""
    ]);

    $url = "http://$server/solr/$core/select?$qs";

    $context = stream_context_create([
        'http' => [
            'header' => "Authorization: Basic " . base64_encode("$username:$password")
        ]
    ]);

    // Use fetchJsonData to allow fallback on failure
    $json = fetchJsonData($url, $context);

    if (!isset($json['facet_counts']['facet_fields']['company_str'])) {
        throw new EndpointNotAvailableException('Date invalide de la Solr');
    }

    $companies = $json['facet_counts']['facet_fields']['company_str'] ?? [];
    $companyCount = 0;
    for ($i = 1; $i < count($companies); $i += 2) {
        if ($companies[$i] > 0) {
            $companyCount++;
        }
    }

    echo json_encode([
        "total" => [
            "jobs" => (int) ($json['response']['numFound'] ?? 0),
            "companies" => (int) $companyCount
        ]
    ]);
} catch (EndpointNotAvailableException $e) {
    $backupServer = rtrim(getenv('BACK_SERVER'), '/');
    $backupUrl = $backupServer . '/mobile/total/';

    try {
        $json = fetchJsonData($backupUrl);

        $obj = [
            'total' => [
                'jobs' => (int) ($json['total'] ?? 0),
                'companies' => $json['companies'] ?? null // dacă vrei și companiile de rezervă
            ]
        ];

        echo json_encode($obj);
    } catch (Exception $backupException) {
        http_response_code(503);
        echo json_encode([
            'error' => 'Ambele endpoint-uri sunt indisponibile.',
            'details' => $backupException->getMessage()
        ]);
    }
}
