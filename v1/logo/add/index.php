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

$method = 'POST';
$server = 'http://zimbor.go.ro/solr/';
$core  = 'auth';
$command ='/update';
$qs = '?_=1617366504771&commitWithin=1000&overwrite=true&wt=json';

$url =  $server.$core.$command.$qs;
 
$data = file_get_contents('php://input');

$options = array(
    'http' => array(
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => $data
    )
);
$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);
if ($result === FALSE) { echo $result; }
?>
