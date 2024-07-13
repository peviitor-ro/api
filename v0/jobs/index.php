<?php
header("Access-Control-Allow-Origin: *");

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

if (isset($_GET["start"])) {
    $start = $_GET["start"];
    $qs .= "&start=" . $start;
}

$url = 'http://' . $server . '/solr/' . $core . '/select'. $qs;

$json = file_get_contents($url);
echo $json;