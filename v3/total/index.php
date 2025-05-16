<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');

require_once '../utils/env.php';

loadEnv('../../api.env');

// Retrieve SOLR variables from environment
$server = getenv('PROD_SERVER') ?: ($_SERVER['PROD_SERVER'] ?? null);
$username = getenv('SOLR_USER') ?: ($_SERVER['SOLR_USER'] ?? null);
$password = getenv('SOLR_PASS') ?: ($_SERVER['SOLR_PASS'] ?? null);

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

try {
    // Debugging: Check if the server is set
    if (!$server) {
        die(json_encode(["error" => "PROD_SERVER is not set in .env"]));
    }

    $core = "jobs";  // Solr core name
    $qs = http_build_query([  // Query parameters for Solr
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

    // Build the Solr URL
    $url = "http://$server/solr/$core/select?$qs";

    // Set up the HTTP context for the request
    $context = stream_context_create([
        'http' => [
            'header' => "Authorization: Basic " . base64_encode("$username:$password")
        ]
    ]);

    // Fetch data from Solr
    $string = @file_get_contents($url, false, $context);

    if ($string === false) {
        throw new EndpointNotAvailableException('Nu s-a putut obține conținutul de la: ' . $url);
    }

    // Decode the JSON response from Solr
    $json = json_decode($string, true);

    if ($json === null || !isset($json['facet_counts']['facet_fields']['company_str'])) {
        http_response_code(500);
        echo json_encode([
            "error" => "Invalid response from Solr",
            "code" => 500,
            "raw_response" => $string
        ]);
        exit;
    }

    // Extract company data from the Solr response
    $companies = $json['facet_counts']['facet_fields']['company_str'] ?? [];
    $companyCount = 0;
    for ($i = 1; $i < count($companies); $i += 2) {
        if ($companies[$i] > 0) {
            $companyCount++;
        }
    }

    // Prepare the final response
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