<?php
header("Access-Control-Allow-Origin: *");
    /**
     * @OA\Get(
     *     path="/v1/background/", tags={"UI"},
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

$qs = $_SERVER['QUERY_STRING'];
$qs = urldecode($qs);

$x = date('w');
$x = $x-1;

$url =  get_master_server().'tech/select?'.'q.op=OR&q='."cat:".$x.'&rows=100';

 
 
$json = file_get_contents($url);


echo $json;

?>
