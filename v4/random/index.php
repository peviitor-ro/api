<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: *');


/**
 * @OA\Get(
 *     path="/v4/random/", tags={"search engine"},
 *     @OA\Response(response="200", description="Success")
 * )
 */

require_once '../config.php';

$core = 'jobs'; 

$qs = '?';
$qs = $qs . 'q=';
$qs = $qs . urlencode('*:*');
$qs = $qs . '&';
$qs = $qs . 'rows=0';

$url = 'http://' . $server . '/solr/' . $core . '/select' . $qs;

$string = file_get_contents($url);
$json = json_decode($string, true);

$max = $json['response']['numFound'];
$start = rand(0, $max-1);

$qs = '?';
$qs = $qs . 'q='; 
$qs = $qs . urlencode('*:*');
$qs = $qs . '&';
$qs = $qs . 'rows=1';
$qs = $qs . '&';
$qs = $qs . 'start=';
$qs = $qs . $start;
$qs = $qs . '&';
$qs = $qs . 'omitHeader=true';

$url = 'http://' . $server . '/solr/' . $core . '/select' . $qs;

$json = file_get_contents($url);
echo $json;
//to do: to add unit tests
