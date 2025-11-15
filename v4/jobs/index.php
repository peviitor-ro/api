<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');

require_once '../utils/env.php';

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



if (isset($_GET["start"])) {
    $start = $_GET["start"];
    $qs .= "&start=" . $start;
    
}

$url =  'https://' . $server . '/solr/' . $core . '/select' . $qs;

// Set up the HTTP context for the request
    $context = stream_context_create([
        'http' => [
            'header' => "Authorization: Basic " . base64_encode("$username:$password")
        ]
    ]);

    // Fetch data from Solr
    $json = @file_get_contents($url, false, $context);

echo $json;
