<?php
header("Access-Control-Allow-Origin: *");


    /**
     * @OA\Get(
     *     path="/v1/random/", tags={"search engine"},
     *     @OA\Response(response="200", description="Success")
     * )
     */

$max=10800;
$start = rand(0,$max);
$qs = 'q=*%3A*&rows=1&start='.$start.'&omitHeader=true';
// $url =  'http://solr.peviitor.ro/solr/shaqodoon/select?'.$qs;
$url =  'http://23.97.216.44/solr/shaqodoon/select?'.$qs;
 
 
$json = file_get_contents($url);
echo $json;



?>