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

$brand = strtolower(urldecode($_GET['brand']));

// Build Solr query URL for brands
$core = "firme";

$qs = "?";
$qs .= "indent=true";
$qs .= "&q.op=OR";
$qs .= "&q=";
$qs .= urlencode('brands:"' . $brand . '"');
$qs .= "&fl=denumire,id";
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

// Send request to Solr for brands
$response = file_get_contents($url, false, $context);

if ($response === false) {
    http_response_code(500);
    echo json_encode(["error" => "Solr request failed"]);
    exit;
}

// Decode the response to filter results
$data = json_decode($response, true);

// Return docs if available for brands
if (isset($data['response']['docs'][0])) {
    $doc = $data['response']['docs'][0];
    echo json_encode([
        "cui" => $doc['id'] ?? null,
        "denumire" => $doc['denumire'][0] ?? null
    ]);
} else {
    // If no results for brands, try searching in denumire field
    $qs2 = "?";
    $qs2 .= "indent=true";
    $qs2 .= "&q.op=OR";
    $qs2 .= "&q=";
    $qs2 .= urlencode('denumire:"' . $brand . '"');
    $qs2 .= "&fl=denumire,id";
    $qs2 .= "&wt=json";

    $url2 = "http://" . $server . "/solr/" . $core . "/select" . $qs2;

    // Send request to Solr for denumire
    $response2 = file_get_contents($url2, false, $context);

    if ($response2 === false) {
        http_response_code(500);
        echo json_encode(["error" => "Solr request failed"]);
        exit;
    }

    $data2 = json_decode($response2, true);

    if (isset($data2['response']['docs'][0])) {
        $doc2 = $data2['response']['docs'][0];
        echo json_encode([
            "cui" => $doc2['id'] ?? null,
            "denumire" => $doc2['denumire'][0] ?? null
        ]);
    } else {
        echo json_encode([]);
    }
}
