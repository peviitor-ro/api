<?php
header("Access-Control-Allow-Origin: *");

/**
 * @OA\Post(
 * path="/v1/update/",
 * tags={"search engine"},
 * @OA\RequestBody(
 *  @OA\MediaType(
 *             mediaType="application/json",
 *     @OA\Schema(
*              
*             type="array",
*             

*              @OA\Items( 
*                   type="object",            
*                   @OA\Property(property="id", type="string", example="95b81d8a-f60a-4efb-a8ba-1369b3b4ee79"),
*                   @OA\Property(property="job_title", type="string", example="test"),
*                   @OA\Property(property="job_link", type="string", example="https://www.epam.com/careers/job-listings/job.55552.software-test-automation-engineer-java_mykolaiv_ukraine"),
*                   @OA\Property(property="company", type="string", example="test"),
*                   @OA\Property(property="country", type="string", example="Romania"),
*                   @OA\Property(property="city", type="string", example="Cluj-Napoca")
*                       )                   
*           )
 *               )
 *                 ),
 *     @OA\Response(response="200", description="Success")
 *               
 * )
 */

$method = 'POST';
$server = 'http://23.97.216.44/solr/';
// $server = 'http://zimbor.go.ro/solr/';
$core  = 'shaqodoon';
$command ='/update';
$qs = '?_=1617366504771&commitWithin=1000&overwrite=true&wt=json';

$url =  $server.$core.$command.$qs;
 
$data = file_get_contents('php://input');





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