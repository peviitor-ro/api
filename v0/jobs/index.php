<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');

require_once '../config.php';

$core = "jobs";

$qs = '?';
$qs = $qs . 'facet=true';
$qs = $qs . '&';
$qs = $qs . 'indent=true';
$qs = $qs . '&';
$qs = $qs . 'q.op=OR';
$qs = $qs . '&';
$qs = $qs . 'q=*%3A*';
$qs = $qs . '&';
$qs = $qs . 'rows=100';
$qs = $qs . '&';
$qs = $qs . 'omitHeader=true';
$qs = $qs . '&';
$qs = $qs . 'useParams=';

$url = 'http://' . $server . '/solr/' . $core . '/select' . $qs;

$string = @file_get_contents($url);
if ($string === FALSE) {
    http_response_code(503);
    echo json_encode([
        "error" => "SOLR server in DEV is down",
        "code" => 503
    ]);
    exit;
}

if (isset($_GET["start"])) {
    $start = $_GET["start"];
    if (!is_numeric($start) || $start <= 0) {
        // Return a JSON error response if start is not a positive number
        echo json_encode(["error" => "You must type a positive number"]);
        exit;
    }
    $qs .= "&start=" . $start;
}

$json = file_get_contents($url);
echo $json;
