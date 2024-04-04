<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: *');


/**
 * @OA\Get(
 *     path="/v1/random/", tags={"search engine"},
 *     @OA\Response(response="200", description="Success")
 * )
 */

function get_master_server()
{
    $method = 'GET';
    $server = "https://api.peviitor.ro/";
    $core  = 'v0';
    $command = '/server/';
    $qs = '';
    $url =  $server . $core . $command . $qs;

    $options = array(
        'http' => array(
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'GET'
        )
    );
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    if ($result === FALSE) { /* Handle error */
    }
    $json = json_decode($result);
    foreach ($json as $item) {
        if ($item->status == "up") {
            return $item->server;
            break;
        }
    }
}

$core = "jobs"; //production
$qs = '?';
$qs = $qs . 'q=' . urlencode('*:*');
$qs = $qs . '&';
$qs = $qs . 'rows=0';
$url = 'http://zimbor.go.ro/solr/' . $core . '/select' . $qs;
$string = file_get_contents($url);
$json = json_decode($string, true);

$server = get_master_server();

$max = $json['response']['numFound'];
$start = rand(0, $max);
$qs = 'q=' . urlencode('*:*'); //query string
$qs = $qs . '&';
$qs = $qs . 'rows=1';
$qs = $qs . '&';
$qs = $qs . 'start=' . $start;
$qs = $qs . '&';
$qs = $qs . 'omitHeader=true';
$url =  $server . $core . '/select?' . $qs;


$json = file_get_contents($url);
echo $json;
//to do: to add unit tests