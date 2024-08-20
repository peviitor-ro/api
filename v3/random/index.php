<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: *');

/**
 * @OA\Get(
 *     path="/v3/random/", tags={"search engine"},
 *     @OA\Response(response="200", description="Success")
 * )
 */

require_once '../config.php';

$core = 'jobs'; //production

$qs = '?';
$qs .= 'q=' . urlencode('*:*');
$qs .= '&';
$qs .= 'rows=0';

$url = 'http://' . $server . '/solr/' . $core . '/select' . $qs;

$string = file_get_contents($url);
$json = json_decode($string, true);

$max = $json['response']['numFound'];
$start = rand(0, $max - 1);

$qs = '?q=' . urlencode('*:*'); //query string
$qs .= '&';
$qs .= 'rows=1';
$qs .= '&';
$qs .= 'start=' . $start;
$qs .= '&';
$qs .= 'omitHeader=true';

$url = 'http://' . $server . '/solr/' . $core . '/select' . $qs;

$json = file_get_contents($url);
echo $json;
//to do: to add unit tests
