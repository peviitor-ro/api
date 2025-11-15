<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');

require_once '../util/loadEnv.php';

loadEnv('../../api.env');

// Retrieve SOLR variables from environment
$server = getenv('PROD_SERVER') ?: ($_SERVER['PROD_SERVER'] ?? null);
$username = getenv('SOLR_USER') ?: ($_SERVER['SOLR_USER'] ?? null);
$password = getenv('SOLR_PASS') ?: ($_SERVER['SOLR_PASS'] ?? null);

$method = 'GET';

$core  = 'jobs';

$qs = '?';
$qs .= 'q=*%3A*';
$qs .= '&';
$qs .= 'rows=100';
$qs .= '&';
$qs .= 'omitHeader=true';

$url =  'https://' . $server . '/solr/' . $core . '/select' . $qs;

if (isset($_GET["start"])) {
    $start = $_GET["start"];
    $qs .= "&start=" . $start;
}

$json = file_get_contents($url);

echo $json;
