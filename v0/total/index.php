<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');

// Ensure the request is a GET request
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["error" => "Only GET method is allowed"]);
    exit;
}

// Load variables from the api.env file
function loadEnv($file) {
    $file = realpath($file); 

    // Check if the api.env file exists
    if (!$file || !file_exists($file)) {
        die(json_encode(["error" => "The api.env file does not exist!", "path" => $file]));
    }

    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments (lines starting with '#')
        if (strpos(trim($line), '#') === 0) continue;

        // Split the line into key and value, and add to environment
        list($key, $value) = explode('=', $line, 2) + [NULL, NULL];
        if ($key && $value) {
            $key = trim($key);
            $value = trim($value);
            $_SERVER[$key] = $value;
            putenv("$key=$value");
        }
    }
}

// Load api.env file
loadEnv('../../api.env');

// Retrieve SOLR variables from environment
$server = getenv('LOCAL_SERVER') ?: ($_SERVER['LOCAL_SERVER'] ?? null);
$username = getenv('SOLR_USER') ?: ($_SERVER['SOLR_USER'] ?? null);
$password = getenv('SOLR_PASS') ?: ($_SERVER['SOLR_PASS'] ?? null);

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
        'header' => "Authorization: Basic " . base64_encode("$username:$password")
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
