<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Methods: GET, POST');
header("Access-Control-Allow-Headers: *");


    /**
     * @OA\Get(
     *     path="/v1/random/", tags={"search engine"},
     *     @OA\Response(response="200", description="Success")
     * )
     */

    function get_server(){
        //get the IP of the server
        //we need a config file to know where is the SOLR
        require('../../_config/index.php');
        return $server;
    }
	

    $url = 'https://api.peviitor.ro/v0/search/?q=*%3A*&rows=0';
    $string = file_get_contents($url);
    $json = json_decode($string, true);
    
$server = get_server();	

$max=$json['response']['numFound'];;
$start = rand(0,$max);
$qs = 'q=*%3A*&rows=1&start='.$start.'&omitHeader=true';

$url =  $server.'shaqodoon/select?'.$qs;
 
 
$json = file_get_contents($url);
echo $json;
?>