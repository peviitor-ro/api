<?php
header("Access-Control-Allow-Origin: *");


$method = 'POST';
//$server = 'http://23.97.216.44/solr/';
$server = 'http://zimbor.go.ro/solr/';
$core  = 'shaqodoon';
$command ='/update';
$qs = '?_=1617366504771&commitWithin=1000&overwrite=true&wt=json';
$company='xtest';

$company = $_POST['company'];

$url =  $server.$core.$command.$qs;
 
$data = "{'delete': {'query': 'company:".$company."'}}";





$url = $server.$core.$command.$qs;



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

var_dump($result);
?>