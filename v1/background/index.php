<?php
header("Access-Control-Allow-Origin: *");
    /**
     * @OA\Get(
     *     path="/v1/background/", tags={"UI"},
     *     @OA\Response(response="200", description="Success")
     * )
     */


$qs = $_SERVER['QUERY_STRING'];
$qs = urldecode($qs);

$x = date('w');
$x = $x-1;
//$url =  'http://solr.peviitor.ro/solr/shaqodoon/select?'.'q.op=OR&q=*%3A*&'."start=".$x;
$url =  'http://23.97.216.44/solr/tech/select?'.'q.op=OR&q='."cat:".$x.'&rows=100';

 
 
$json = file_get_contents($url);


echo $json;

?>