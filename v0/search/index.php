<?php
header("Access-Control-Allow-Origin: *");

require_once '../config.php';

$core = 'jobs';
$qs = '?';
$qs = $qs . $_SERVER['QUERY_STRING'];

$url = 'http://' . $server . '/solr/' . $core . '/select' . $qs;

$json = file_get_contents($url);
echo $json;
?>