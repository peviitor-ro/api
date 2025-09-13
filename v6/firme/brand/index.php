<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');

// Allow only GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["error" => "Only GET method is allowed"]);
    exit;
}

// Load variables from api.env
require_once __DIR__ . '/../../../util/loadEnv.php';
loadEnv(__DIR__ . '/../../../api.env');

// Get Solr connection details from .env
$server   = getenv('PROD_SERVER') ?: ($_SERVER['PROD_SERVER'] ?? null);
$username = getenv('SOLR_USER')    ?: ($_SERVER['SOLR_USER'] ?? null);
$password = getenv('SOLR_PASS')    ?: ($_SERVER['SOLR_PASS'] ?? null);

// If server is not set, stop execution
if (!$server) {
    http_response_code(500);
    echo json_encode(["error" => "PROD_SERVER is not set in api.env"]);
    exit;
}

// Check for required parameter
if (!isset($_GET['brand']) || $_GET['brand'] === '') {
    http_response_code(400);
    echo json_encode(["error" => "Missing brand parameter"]);
    exit;
}

$brand = $_GET['brand'];

// Build Solr query URL
$core = "firme";
$query = urlencode('brands:"' . $brand . '"');
$url = "http://{$server}/solr/{$core}/select"
     . "?indent=true"
     . "&q.op=OR"
     . "&q={$query}"
     . "&fl=denumire,cui"
     . "&wt=json";

// Prepare HTTP context with Basic Auth
$options = [
    'http' => [
        'method' => 'GET',
        'header' => 
            "Accept: application/json\r\n" .
            "Authorization: Basic " . base64_encode("$username:$password") . "\r\n" .
            "User-Agent: PHP-FileGetContents\r\n"
    ]
];
$context = stream_context_create($options);

// Send request to Solr
$response = file_get_contents($url, false, $context);

if ($response === false) {
    http_response_code(500);
    echo json_encode(["error" => "Solr request failed"]);
    exit;
}

// Decode the response to filter results
$data = json_decode($response, true);

// Return only docs if available
if (isset($data['response']['docs'])) {
    echo json_encode($data['response']['docs']);
} else {
    echo json_encode([]);
}
