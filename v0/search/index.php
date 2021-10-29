<?php

header("Access-Control-Allow-Origin: *");

$qs = $_SERVER['QUERY_STRING'];

// $url =  'http://solr.peviitor.ro/solr/shaqodoon/select?'.$qs;

$url =  'http://23.97.216.44/solr/shaqodoon/select?'.$qs;

 

 

$json = file_get_contents($url);





echo $json;







?>