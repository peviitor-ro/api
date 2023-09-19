<?php
header("Access-Control-Allow-Origin: *");
$qs = $_SERVER['QUERY_STRING'];
function get_server(){
    //get the IP of the server
    //we need a config file to know where is the SOLR
    require('../../_config/index.php');
    return $server;
}
$core ="jobs";
$url =  get_server()[1].$core.'/select?'.$qs;
$json = file_get_contents($url);
echo $json;
?>
