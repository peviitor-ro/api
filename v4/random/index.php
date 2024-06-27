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

$server = 'zimbor.go.ro';
$core = 'jobs'; //production
$qs = '?';
$qs = $qs . 'q=' . urlencode('*:*');
$qs = $qs . '&';
$qs = $qs . 'rows=0';
$url = 'http://' .$server .'/solr/' . $core . '/select' . $qs;
$string = file_get_contents($url);
$json = json_decode($string, true);

$max = $json['response']['numFound'];
$start = rand(0, $max-1);
$qs = '?q=' . urlencode('*:*'); //query string
$qs = $qs . '&';
$qs = $qs . 'rows=1';
$qs = $qs . '&';
$qs = $qs . 'start=' . $start;
$qs = $qs . '&';
$qs = $qs . 'omitHeader=true';
$url = 'http://' .$server .'/solr/' . $core . '/select' . $qs;


$json = file_get_contents($url);
echo $json;
//to do: to add unit tests
