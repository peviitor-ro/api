<?php
header("Access-Control-Allow-Origin: *");
$qs = $_SERVER['QUERY_STRING'];

function get_server(){
    //get the IP of the server
    //we need a config file to know where is the SOLR
    require('../../../_config/index.php');
    return $server;
}

function get_master_server(){
    $method = 'GET';
    $server = "https://api.peviitor.ro/";
    $core  = 'v0';
    $command ='/server/';
    $qs = '';
    $url =  $server.$core.$command.$qs;
   
    $options = array(
        'http' => array(
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'GET',
            'content' => $data
        )
    );
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    if ($result === FALSE) { /* Handle error */ }
    $json = json_decode($result);
    foreach($json as $item)
        {
            echo $item->server;
            echo $item->status;
        }
}

get_master_server();
/*
$core ="shaqodoon";
$url =  get_server().$core.'/select?'.$qs;
$json = file_get_contents($url);
echo $json;
*/
?>
