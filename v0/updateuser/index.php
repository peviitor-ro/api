<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, OPTIONS");
header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
header('Content-Type: application/json; charset=utf-8');

require_once '../config.php';

$core = 'auth';
$qs = '?omitHeader=true&q.op=OR&q=apikey%3A';

// Retrieve parameters from the query string
$apikey = isset($_GET['apikey']) ? trim($_GET['apikey']) : null;
$id = isset($_GET['id']) ? trim($_GET['id']) : null;
$urlParam = isset($_GET['url']) ? trim($_GET['url']) : null;
$company = isset($_GET['company']) ? trim($_GET['company']) : null;
$logo = isset($_GET['logo']) ? trim($_GET['logo']) : null;

if (!$apikey) {
    http_response_code(400);
    echo json_encode(["error" => "Missing required parameter: apikey"]);
    exit;
}

$apikey = urlencode($apikey);
$url = 'http://' . $server . '/solr/' . $core . '/select' . $qs . $apikey;

$authHeader = "Authorization: Basic " . base64_encode("$username:$password") . "\r\n";

$context = stream_context_create([
    'http' => [
        'header' => $authHeader
    ]
]);

// Fetch data from Solr
$string = @file_get_contents($url, false, $context);

// Check if Solr is down (server not responding)
if ($string === false) {
    http_response_code(503);
    echo json_encode([
        "error" => "SOLR server in DEV is down",
        "code" => 503
    ]);
    exit;
}

$json = json_decode($string);

if (!isset($json->response->docs[0])) {
    http_response_code(404);
    echo json_encode(["error" => "User with this apikey not found"]);
    exit;
}

$doc = $json->response->docs[0];

// Remove version field if present
unset($doc->_version_);
unset($doc->_root_);

// Update user fields if provided
if ($id) $doc->id = $id;
if ($urlParam) $doc->url = $urlParam;
if ($company) $doc->company = $company;
if ($logo) $doc->logo = $logo;

// Convert updated document into JSON
$data = json_encode([$doc]);

$options = [
    'http' => [
        'header'  => "Content-type: application/json\r\n" . $authHeader,
        'method'  => 'POST',
        'content' => $data
    ]
];

$context = stream_context_create($options);
$url = 'http://' . $server . '/solr/' . $core . '/update?commitWithin=1000&overwrite=true&wt=json';

$result = file_get_contents($url, false, $context);

if ($result === FALSE) {
    http_response_code(500);
    echo json_encode(["error" => "Failed to update Solr."]);
    exit;
}

echo $data;