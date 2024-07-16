<?php
header("Access-Control-Allow-Origin: *");

require_once '../config.php';

$core = 'jobs';
$qs = '?';
$qs = $qs . $_SERVER['QUERY_STRING'];

if ( $_SERVER['QUERY_STRING'] == "page=1") {$qs .= "&q=*%3A*";} 
$url = 'http://' . $server . '/solr/' . $core . '/select' . $qs;


$json = file_get_contents($url);
echo $json;
?>