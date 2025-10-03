<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../bootstrap.php';
$GLOBALS['solr'] = getSolrCredentials('LOCAL');

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
    echo json_encode(["error" => "LOCAL_SERVER is not set in api.env"]);
    exit;
}

// Check if POST parameters are present
if (!isset($_POST['id']) || !isset($_POST['website'])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing id or website"]);
    exit;
}

// Read values from POST
$id     = $_POST['id'];
$website = $_POST['website'];

// Build JSON payload for Solr update
$payload = [
    [
        "id" => $id,
        "website" => ["add" => $website]
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
