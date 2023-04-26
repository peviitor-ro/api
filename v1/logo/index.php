<?php
header("Access-Control-Allow-Origin: *");
$qs = "indent=true&q.op=OR&q=logo%3A*&rows=10000&omitHeader=true&useParams=";

function get_server(){
    //get the IP of the server
    //we need a config file to know where is the SOLR
    require('../../_config/index.php');
    return $server;
}
$core ="auth";
$url =  get_server().$core.'/select?'.$qs;

$string = file_get_contents($url);
$json = json_decode($string, true);
$companies = $json['response']['docs'];


$results =  new stdClass();
$results->companies = array();
$results->companies = $companies;


$test = array();
foreach($companies as $company) 
{
   
    echo "<br/> ";
    $item = $company["id"];
    $xurl  =  $company["logo"];
    $url  = $xurl[0];
    $test[$item] = $url;
     var_dump($test);
}

//echo json_encode($results);
?>
