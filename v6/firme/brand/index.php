<?php
require_once __DIR__ . '/../../bootstrap.php';

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

// Allow only GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); // Method Not Allowed
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
     . "&fl=denumire,id"
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
if (isset($data['response']['docs'][0])) {
    $doc = $data['response']['docs'][0];
    echo json_encode([
        "cui" => $doc['id'] ?? null,
        "denumire" => $doc['denumire'][0] ?? null
    ]);
} else {
    echo json_encode([]);
}
