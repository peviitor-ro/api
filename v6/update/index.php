<?php
header("Access-Control-Allow-Origin: *");


$method = 'POST';
$server = 'http://zimbor.go.ro:8985/solr/';
$core  = 'jobs';
$command ='/update';
$qs = '?_=1617366504771&commitWithin=1000&overwrite=true&wt=json';


$data = file_get_contents('php://input');
$json = json_decode($data);
print_r($data);
   
foreach ($json as $item) {
    $item->title=html_entity_decode($item->title);
    $item->id = md5($item->job_link)."";
 
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


$url =  $server.$core.$command.$qs;
$result = file_get_contents($url, false, $context);



?>
