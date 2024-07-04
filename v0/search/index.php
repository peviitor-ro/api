<?php
header("Access-Control-Allow-Origin: *");

$server = '172.18.0.10:8983';
$core = 'jobs';
$qs = '?';
$qs = $qs . $_SERVER['QUERY_STRING'];

$url = 'http://' . $server . '/solr/' . $core . '/select' . $qs;

$json = file_get_contents($url);
echo $json;
?>