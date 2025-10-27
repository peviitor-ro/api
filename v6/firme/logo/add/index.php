<?php
require_once __DIR__ . '/../../../bootstrap.php';

// Verificăm headerul Origin al cererii
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
} else {
    header("Access-Control-Allow-Origin: $allowed_origins"); // fallback
}

header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit; 
}

header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');

$GLOBALS['solr'] = getSolrCredentials('PROD');

// Allow only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["error" => "Only POST method is allowed"]);
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

// If server is not set, stop execution
if (!$server) {
    http_response_code(500);
    echo json_encode(["error" => "PROD_SERVER is not set in api.env"]);
    exit;
}

// Check if POST parameters are present
if (!isset($_POST['id']) || !isset($_POST['logo'])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing id or logo"]);
    exit;
}

// Read values from POST
$id     = $_POST['id'];
$logo = $_POST['logo'];

// Validate lengths
$MAX_ID_LENGTH = 100;
$MAX_LOGO_LENGTH = 2048;

$errors = [];

if (strlen($id) > $MAX_ID_LENGTH) {
    $errors['id'] = "id exceeds maximum length of {$MAX_ID_LENGTH} characters";
}

if (strlen($logo) > $MAX_LOGO_LENGTH) {
    $errors['logo'] = "logo exceeds maximum length of {$MAX_LOGO_LENGTH} characters";
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode([
        "error" => "Validation failed",
        "details" => $errors
    ]);
    exit;
}

// Validate logo format
if (!preg_match('/^https?:\/\/[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}(\/.*)?$/', $logo)) {
    http_response_code(400);
    echo json_encode([
        "error" => "Invalid logo URL. It must start with http:// or https:// and be a valid domain.",
        "received" => $logo
    ]);
    exit;
}

// Build JSON payload for Solr update
$payload = [
    [
        "id" => $id,
        "logo" => ["add" => $logo]
    ]
];
$jsonPayload = json_encode($payload);

// Solr update API endpoint
$core = "firme";
$url = "http://{$server}/solr/{$core}/update?commitWithin=1000&overwrite=true&wt=json";

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
