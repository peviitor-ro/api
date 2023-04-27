<?php
header("Access-Control-Allow-Origin: *");

$method = 'POST';
$server = 'http://zimbor.go.ro/solr/';
$core  = 'auth';
$command ='/update';
$qs = '?_=1617366504771&commitWithin=1000&overwrite=true&wt=json';

$url =  $server.$core.$command.$qs;
 
$data = file_get_contents('php://input');
$json = json_decode($data);
print_r($data);
   foreach ($json as $item) {
    $item->id=html_entity_decode($item->company);
    $item->logo = $item->logo;
    
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
$result = file_get_contents($url, false, $context);
if ($result === FALSE) { /* Handle error */ }
?>
