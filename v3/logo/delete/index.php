<?php
header("Access-Control-Allow-Origin: *");

$method = 'POST';

require_once '../../config.php';

$core  = 'auth';
$command = '/update';

$qs = '?';
$qs .= '_=1617366504771';
$qs .= '&';
$qs .= 'commitWithin=1000';
$qs .= '&';
$qs .= 'overwrite=true';
$qs .= '&';
$qs .= 'wt=json';

$url = 'http://' . $server . '/solr/' . $core . $command . $qs;

$company = $_POST['company'];
$data = "{'delete': {'query': 'id:" . $company . "'}}";

$options = array(
    'http' => array(
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => $data
    )
);
$context  = stream_context_create($options);

$result = file_get_contents($url, false, $context);
if ($result === FALSE) {
    echo $result;
}
