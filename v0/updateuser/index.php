<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PATCH");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json; charset=utf-8');

// Allow ONLY PATCH
if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["error" => "Only PATCH method is allowed"]);
    exit;
}

// Load variables from the api.env file
require_once __DIR__ . '/../../util/loadEnv.php';
loadEnv(__DIR__ . '/../../api.env');

// SOLR connection variables
$server = getenv('LOCAL_SERVER') ?: ($_SERVER['LOCAL_SERVER'] ?? null);
$username = getenv('SOLR_USER') ?: ($_SERVER['SOLR_USER'] ?? null);
$password = getenv('SOLR_PASS') ?: ($_SERVER['SOLR_PASS'] ?? null);

if (!$server) {
    die(json_encode(["error" => "LOCAL_SERVER is not set in api.env"]));
}

$core = 'auth';
$qs = '?omitHeader=true&q.op=OR&q=apikey%3A';

// Retrieve parameters from the PATCH request
$input = json_decode(file_get_contents("php://input"), true);
$apikey = isset($input['apikey']) ? trim($input['apikey']) : null;
$id = isset($input['id']) ? trim($input['id']) : null;
$urlParam = isset($input['url']) ? trim($input['url']) : null;
$company = isset($input['company']) ? trim($input['company']) : null;
$logo = isset($input['logo']) ? trim($input['logo']) : null;

if (!$apikey) {
    http_response_code(400);
    echo json_encode(["error" => "Missing required parameter: apikey"]);
    exit;
}

$invalid_chars = '/[\s\/,<>+=\-:;?"\'\{\}\[\]\|\\\)\(\*&^%$#!~`]/';

if (!preg_match('/^[a-zA-Z0-9._%+-]{3,}@[a-zA-Z0-9.-]/', $id) || preg_match($invalid_chars, $id)) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid ID format: special characters and spaces are not allowed. Please try something like `local-part@domain`", "received" => $id]);
    exit;
}

if (!preg_match('/[a-zA-Z0-9]/', $apikey)) {
    http_response_code(400);
    echo json_encode([
        "error" => "Invalid apikey. It must contain only letters and numbers.",
        "received" => $apikey
    ]);
    exit;
}

if (!preg_match('/^https?:\/\/[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}(\/.*)?$/', $urlParam)) {
    http_response_code(400);
    echo json_encode([
        "error" => "Invalid logo URL. It must start with http:// or https:// and be a valid domain.",
        "received" => $urlParam
    ]);
    exit;
}

if (!preg_match('/^https?:\/\/[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}(\/.*)?$/', $logo)) {
    http_response_code(400);
    echo json_encode([
        "error" => "Invalid logo URL. It must start with http:// or https:// and be a valid domain.",
        "received" => $logo
    ]);
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

unset($doc->_version_);
unset($doc->_root_);

// Update user fields if provided
if ($id) $doc->id = $id;
if ($urlParam) $doc->url = $urlParam;
if ($company) $doc->company = $company;
if ($logo) $doc->logo = $logo;

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

// Return the updated document
echo $data;
