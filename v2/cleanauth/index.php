<?php


function get_server(){
    //get the IP of the server
    //we need a config file to know where is the SOLR
    require_once('../../_config/index.php');
    return $server;
  }

$method = 'POST';
$server = get_server();
$core  = 'auth';
$command ='/update';
$qs = '?_=1617366504771&commitWithin=1000&overwrite=true&wt=json';
$url =  $server.$core.$command.$qs;
 $data = "{'delete': {'query': '*:*'}}";
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
