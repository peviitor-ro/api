<?php
header("Access-Control-Allow-Origin: *");

$server = '172.18.0.10:8983';
$method = 'POST';
$core  = 'jobs';
$command = '/update';

$qs = '?';
$qs = $qs . '_=1617366504771';
$qs = $qs . '&';
$qs = $qs . 'commitWithin=1000';
$qs = $qs . '&';
$qs = $qs . 'overwrite=true';
$qs = $qs . '&';
$qs = $qs . 'wt=json';

$url = 'http://' . $server . '/solr/' . $core . $command . $qs;

$company = $_POST['company'];
$data = "{'delete': {'query': 'hiringOrganization.name:" . $company . "'}}";

$options = array(
    'http' => array(
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => $data
    )
);


$context  = stream_context_create($options);

$result = file_get_contents($url, false, $context);
if ($result === FALSE) { /* Handle error */
}
