<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');

require_once '../config.php';

$core = "auth";

$qs = '?';
$qs = $qs . 'indent=true';
$qs = $qs . '&';
$qs = $qs . 'q.op=OR';
$qs = $qs . '&';
$qs = $qs . 'q=logo%3A*';
$qs = $qs . '&';
$qs = $qs . 'rows=20000000';
$qs = $qs . '&';
$qs = $qs . 'useParams=';

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
?>
