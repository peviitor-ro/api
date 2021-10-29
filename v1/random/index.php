<?php
header("Access-Control-Allow-Origin: *");


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
	
	
$server = get_server();	

$max=10800;
$start = rand(0,$max);
$qs = 'q=*%3A*&rows=1&start='.$start.'&omitHeader=true';

$url =  $server.'shaqodoon/select?'.$qs;
 
 
$json = file_get_contents($url);
echo $json;



?>