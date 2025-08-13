<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');

// Allow only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["error" => "Only POST method is allowed"]);
    exit;
}

// Load variables from api.env
require_once __DIR__ . '/../../../../util/loadEnv.php';
loadEnv(__DIR__ . '/../../../../api.env');

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

// Check if POST parameters are present
if (!isset($_POST['id']) || !isset($_POST['brand'])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing id or brand"]);
    exit;
}

// Read values from POST
$id     = $_POST['id'];
$brand = $_POST['brand'];

// Build JSON payload for Solr update
$payload = [
    [
        "id" => $id,
        "brand" => ["add" => $brand]
    ]
];
$jsonPayload = json_encode($payload);

// Solr update API endpoint
$core = "firme";

$command = "/update";

$qs = "?";
$qs .= "commitWithin=1000";
$qs .= "&overwrite=true";
$qs .= "&wt=json";

$url = "http://" . $server . "/solr/" .$core . $command . $qs;

$authHeader = "Authorization: Basic " . base64_encode("$username:$password") . "\r\n";

// Set HTTP context for file_get_contents
$options = [
    'http' => [
        'method'  => 'POST',
        'header'  => 
            "Accept: application/json, text/plain, */*\r\n" .
            "Content-Type: application/json\r\n" .
            "Authorization: Basic " . base64_encode("$username:$password") . "\r\n" .
            "User-Agent: PHP-FileGetContents\r\n" .
            "Origin: http://{$server}\r\n",
        'content' => $jsonPayload,
        'ignore_errors' => true // Allow reading error responses from Solr
    ]
];
$context = stream_context_create($options);

// Send request to Solr
$response = file_get_contents($url, false, $context);

// Handle and return the Solr response
if ($response === false) {
    http_response_code(500);
    echo json_encode(["error" => "Solr request failed"]);
} else {
    echo $response;
}
