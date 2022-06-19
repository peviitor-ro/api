<?php
header("Access-Control-Allow-Origin: *");
    /**
     * 
     * @OA\Post(
     *     path="/v1/clean/", 
     *     tags={"search engine"},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="company",
     *                     type="string"
     *                 ),
     *                 example="company=Endava"
     *                      )
     *                  )
     *          ),
    
     *     @OA\Response(response="200", description="Success")
     * )
     */



$method = 'POST';


$server = 'http://zimbor.go.ro/solr/';
$core  = 'shaqodoon';
$command ='/update';
$qs = '?_=1617366504771&commitWithin=1000&overwrite=true&wt=json';
$company='xtest';

$company = $_POST['company'];

$url =  $server.$core.$command.$qs;
 
$data = "{'delete': {'query': 'company:".$company."'}}";





$url = $server.$core.$command.$qs;



$options = array(
    'http' => array(
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => $data
    )
);
$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);
if ($result === FALSE) { /* Handle error */ }

var_dump($result);
?>