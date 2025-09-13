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
$server   = getenv('LOCAL_SERVER') ?: ($_SERVER['LOCAL_SERVER'] ?? null);
$username = getenv('SOLR_USER')    ?: ($_SERVER['SOLR_USER'] ?? null);
$password = getenv('SOLR_PASS')    ?: ($_SERVER['SOLR_PASS'] ?? null);

// If server is not set, stop execution
if (!$server) {
    http_response_code(500);
    echo json_encode(["error" => "LOCAL_SERVER is not set in api.env"]);
    exit;
}

// Check for required parameter
if (!isset($_GET['scraper']) || $_GET['scraper'] === '') {
    http_response_code(400);
    echo json_encode(["error" => "Missing scraper parameter"]);
    exit;
}

$scraper = $_GET['scraper'];

// Build Solr query URL
$core = "firme";

$qs = "?";
$qs .= "indent=true";
$qs .= "&q.op=OR";
$qs .= "&q=";
$qs .= urlencode('scraper:"' . $scraper . '"');
$qs .= "&fl=denumire,cui";
$qs .= "&wt=json";


$url = "http://" . $server . "/solr/" . $core . "/select" . $qs;

$authHeader = "Authorization: Basic " . base64_encode("$username:$password") . "\r\n";

// Prepare HTTP context with Basic Auth
$options = [
    'http' => [
        'method' => 'GET',
        'header' => 
            "Accept: application/json\r\n" .
            $authHeader .
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
