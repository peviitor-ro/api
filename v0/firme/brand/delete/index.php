<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');

// Allow only DELETE requests
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["error" => "Only DELETE method is allowed"]);
    exit;
}

// Load variables from api.env
require_once __DIR__ . '/../../../../util/loadEnv.php';
loadEnv(__DIR__ . '/../../../../api.env');

// Get Solr connection details from .env
$server   = getenv('LOCAL_SERVER') ?: ($_SERVER['LOCAL_SERVER'] ?? null);
$username = getenv('SOLR_USER')    ?: ($_SERVER['SOLR_USER'] ?? null);
$password = getenv('SOLR_PASS')    ?: ($_SERVER['SOLR_PASS'] ?? null);

if (!$server) {
    http_response_code(500);
    echo json_encode(["error" => "LOCAL_SERVER is not set in api.env"]);
    exit;
}

// Read and decode JSON body from request
$input = json_decode(file_get_contents("php://input"), true);

// Validate required parameters
if (!isset($input['id']) || !isset($input['brands'])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing id or brands"]);
    exit;
}

$id     = $input['id'];
$brands = $input['brands']; // can be string or array

// Build JSON payload for Solr field removal
$payload = [
    [
        "id" => $id,
        "brands" => ["remove" => $brands]
    ]
];
$jsonPayload = json_encode($payload);

// Solr update API endpoint
$core = "firme";
$url = "http://{$server}/solr/{$core}/update?commitWithin=1000&overwrite=true&wt=json";

// Prepare HTTP context for DELETE request with body
$options = [
    'http' => [
        'method'  => 'POST', // ⚠️ Solr API doesn't support DELETE for partial updates, must still be POST
        'header'  => 
            "Accept: application/json, text/plain, */*\r\n" .
            "Content-Type: application/json\r\n" .
            "Authorization: Basic " . base64_encode("$username:$password") . "\r\n" .
            "User-Agent: PHP-FileGetContents\r\n" .
            "Origin: http://{$server}\r\n",
        'content' => $jsonPayload,
        'ignore_errors' => true
    ]
];
$context = stream_context_create($options);

// Send update request to Solr
$response = file_get_contents($url, false, $context);

// Output response from Solr
if ($response === false) {
    http_response_code(500);
    echo json_encode(["error" => "Solr request failed"]);
} else {
    echo $response;
}
