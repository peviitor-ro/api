<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../bootstrap.php';
$GLOBALS['solr'] = getSolrCredentials('LOCAL');

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
    echo json_encode(["error" => "LOCAL_SERVER is not set in api.env"]);
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

$qs = "?";
$qs .= "indent=true";
$qs .= "&q.op=OR";
$qs .= "&q=";
$qs .= urlencode('brands:"' . $brand . '"');
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
$docs = $data['response']['docs'];

    foreach ($docs as &$doc) {
        if (isset($doc['denumire']) && is_array($doc['denumire'])) {
            $doc['denumire'] = $doc['denumire'][0];
        }
        if (isset($doc['cui']) && is_array($doc['cui'])) {
            $doc['cui'] = $doc['cui'][0];
        }
    }

    echo json_encode($docs, JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([]);
}