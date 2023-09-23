<?php
header("Access-Control-Allow-Origin: *");
 /**
     * 
     * @OA\Post(
     *     path="/v1/logo/add/", 
     *     tags={"LOGO"},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/json",
      *     @OA\Schema(
*              
*             type="array",
*             

*              @OA\Items( 
*                   type="object",            
*                   @OA\Property(property="id", type="string", example="Nova"),
*                   @OA\Property(property="logo", type="string", example="https://e-infra.ro/wp-content/uploads/2023/02/logotype-nova.jpg"),
*                   
*                       )                   
*           )
     *                  )
     *          ),
    
     *     @OA\Response(response="200", description="Success")
     * )
     */


 function get_server(){
    //get the IP of the server
    //we need a config file to know where is the SOLR
    require('../../../_config/index.php');
    return $server;
}

$method = 'POST';
$server = get_server();
$core  = 'auth';
$command ='/update';
$qs = '?_=1617366504771&commitWithin=1000&overwrite=true&wt=json';


 
$data = file_get_contents('php://input');

$options = array(
    'http' => array(
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => $data
    )
);
$context  = stream_context_create($options);

foreach ($server as $solrurl) {
$url =  $solrurl.$core.$command.$qs;
$result = file_get_contents($url, false, $context);
if ($result === FALSE) { echo $result; }
}

?>
