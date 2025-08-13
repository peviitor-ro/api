<?php
require_once __DIR__ . '/../bootstrap.php';

// Ensure the request is a GET request
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["error" => "Only GET method is allowed"]);
    exit;
}

$solr = $GLOBALS['solr'] ?? null;
$authHeader = $GLOBALS['authHeader'] ?? null;

if (!$solr || !$authHeader) {
    echo json_encode(["error" => "Solr credentials or auth header not set"]);
    exit;
}

$server = $solr['server'];
$username = $solr['username'];
$password = $solr['password'];

// Debugging: Check if the server is set
if (!$server) {
    die(json_encode(["error" => "LOCAL_SERVER is not set in api.env"]));
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
            'header' => $authHeader
        ]
    ]);

// Fetch data from Solr
$string = @file_get_contents($url, false, $context);

if ($string === false) {
    $error = error_get_last();  // Get the last error
    http_response_code(503);
    echo json_encode([
        "error" => "SOLR server in DEV is down",
        "code" => 503,
        "details" => $error
    ]);
    exit;
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
?>
