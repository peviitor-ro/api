<?php
header("Access-Control-Allow-Origin: *");

    /**
     * @OA\Get(
     *     path="/v1/random/", tags={"search engine"},
     *     @OA\Response(response="200", description="Success")
     * )
     */


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
            if ($item->status=="up"){
                return $item->server;
                break;
            }
        }
}

$max=50000; // max should be extracted from SOLR not hardcoded -- TODO!!!
$start = rand(0,$max);
$qs = 'q=*%3A*&rows=1&start='.$start.'&omitHeader=true';
 $url =  get_master_server().'jobs/select?'.$qs;
  
$json = file_get_contents($url);
echo $json;
?>
