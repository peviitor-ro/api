<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');

// Ensure the request is GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["error" => "Only GET method is allowed"]);
    exit;
}

// Load variables from the api.env file
require_once __DIR__ . '/../../util/loadEnv.php';
loadEnv(__DIR__ . '/../../api.env');

// Retrieve SOLR variables from environment
$server = getenv('LOCAL_SERVER') ?: ($_SERVER['LOCAL_SERVER'] ?? null);
$username = getenv('SOLR_USER') ?: ($_SERVER['SOLR_USER'] ?? null);
$password = getenv('SOLR_PASS') ?: ($_SERVER['SOLR_PASS'] ?? null);

// Debugging: Check if the server is set
if (!$server) {
    die(json_encode(["error" => "LOCAL_SERVER is not set in api.env"]));
}

$core  = 'logo';

$qs = '?indent=true&q.op=OR&q=*%3A*&rows=1000000000&useParams=';

$url = 'http://' . $server . '/solr/' . $core . '/select' . $qs;

$context = stream_context_create([
    'http' => [
        'header' => "Authorization: Basic " . base64_encode("$username:$password")
    ]
]);

// Fetch data from Solr
$string = file_get_contents($url, true, $context);

// Check if Solr is down (server not responding)
if ($string === false) {
    $error = error_get_last();
    http_response_code(503);
    echo json_encode([
        "error" => "SOLR request failed: " . $error['message'],
        "code" => 503
    ]);
    exit;
}

$json = json_decode($string, true);

$companies = $json['response']['docs'];

$results = new stdClass();
$results->total = count($companies);
$results->companies = array();

for ($i = 0; $i < count($companies); $i++) {
    $obj  = new stdClass();
    $obj->name = $companies[$i]["id"];
    $obj->logo = $companies[$i]["logo"][0];
    $results->companies[$i] = $obj;
}

echo json_encode($results);
