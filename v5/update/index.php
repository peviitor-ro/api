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

foreach ($json as $item) {

    $item->job_title  = html_entity_decode($item->job_title);
    $item->country    = str_replace("Romania", "România", $item->country);
    $item->city    = str_replace("Cluj Napoca", "Cluj-Napoca", $item->city);
    $item->city    = str_replace("Bucharest", "București", $item->city);
    $item->city    = str_replace("Bucuresti", "București", $item->city);
    $item->id = md5($item->job_link) . "";
    $company = $item->company;
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

$url = 'http://' . $server . '/solr/' . $core . $command . $qs;

$result = file_get_contents($url, false, $context);
if ($result === FALSE) { /* Handle error */
}
