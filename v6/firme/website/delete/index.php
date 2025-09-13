<?php
// Permit doar anumite origini
$allowed_origins = ['https://admin.zira.ro'];

// Verificăm headerul Origin al cererii
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    http_response_code(403); // Forbidden
    exit('Origin not allowed');
}

header("Access-Control-Allow-Methods: DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Respond to preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Stop script from executing further, return only headers and 200 OK status
    http_response_code(200);
    exit;
}


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
$server   = getenv('PROD_SERVER') ?: ($_SERVER['PROD_SERVER'] ?? null);
$username = getenv('SOLR_USER')    ?: ($_SERVER['SOLR_USER'] ?? null);
$password = getenv('SOLR_PASS')    ?: ($_SERVER['SOLR_PASS'] ?? null);

if (!$server) {
    http_response_code(500);
    echo json_encode(["error" => "PROD_SERVER is not set in api.env"]);
    exit;
}

// Read and decode JSON body from request
$input = json_decode(file_get_contents("php://input"), true);

// Validate required parameters
if (!isset($input['id']) || !isset($input['website'])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing id or website"]);
    exit;
}

$id     = $input['id'];
$website = $input['website']; // can be string or array

// Build JSON payload for Solr field removal
$payload = [
    [
        "id" => $id,
        "website" => ["remove" => $website]
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
