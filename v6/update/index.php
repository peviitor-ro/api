<?php
header("Access-Control-Allow-Origin: *");

require_once '../config.php';

$method = 'POST';
$core  = 'jobs';
$command = '/update';

$qs = '?';
$qs .= '_=1617366504771';
$qs .= '&';
$qs .= 'commitWithin=1000';
$qs .= '&';
$qs .= 'overwrite=true';
$qs .= '&';
$qs .= 'wt=json';

$data = file_get_contents('php://input');
$json = json_decode($data);
print_r($data);

foreach ($json as $item) {
    $item->title = html_entity_decode($item->title);
    $item->id = md5($item->job_link) . "";
}

$data = json_encode($json);

$options = array(
    'http' => array(
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => $data
    )
);

$context  = stream_context_create($options);

$url =  'http://' . $server . '/solr/' . $core . $command . $qs;

$result = file_get_contents($url, false, $context);
