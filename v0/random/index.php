<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: *');



//calculează nr max de job-uri
$server = '172.18.0.10:8983'; //DEV
$core = 'jobs'; 
$qs = '?';
$qs = $qs . 'q=' . urlencode('*:*');
$qs = $qs . '&';
$qs = $qs . 'rows=0';
$url = 'http://' .$server .'/solr/' . $core . '/select' . $qs;
$string = file_get_contents($url);
$json = json_decode($string, true);

$max = $json['response']['numFound'];
$start = rand(0, $max-1);//alege un job random 
//pana aici
//extrage din solr job-ul ales
$qs = '?q=' . urlencode('*:*'); //query string
$qs = $qs . '&';
$qs = $qs . 'rows=1';
$qs = $qs . '&';
$qs = $qs . 'start=' . $start;
$qs = $qs . '&';
$qs = $qs . 'omitHeader=true';
$url = 'http://' .$server .'/solr/' . $core . '/select' . $qs;

$json = file_get_contents($url);//executa
echo $json;//afiseaza
//pana aici
//to do: to add unit tests
