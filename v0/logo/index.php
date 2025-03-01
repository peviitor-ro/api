<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');

// Ensure the request is GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["error" => "Only GET method is allowed"]);
    exit;
}


$core  = 'logo';

$qs = '?indent=true&q.op=OR&q=*%3A*&useParams=';

$url = 'http://' . $server . '/solr/' . $core . '/select' . $qs;

$context = stream_context_create([
    'http' => [
        'header' => "Authorization: Basic " . base64_encode("$username:$password")
    ]
]);

// Fetch data from Solr
$string = file_get_contents($url, false, $context);

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
