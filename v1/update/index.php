<?php
header("Access-Control-Allow-Origin: *");

/**
 * @OA\Post(
 * path="/v1/update/",
 * tags={"scraper"},
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
*                   @OA\Property(property="remote", type="string", example="remote"),
*                   @OA\Property(property="city", type="string", example="Cluj-Napoca")
*                       )                   
*           )
 *               )
 *                 ),
 *     @OA\Response(response="200", description="Success")
 *               
 * )
 */


 function city_fix($in){
     $output = $in;
     $output = str_replace("Bucharest","București",$output);
     $output = str_replace("Brasov","Brașov",$output);
     $output = str_replace("Timisoara","Timișoara",$output);
     $output = str_replace("Pitesti","Pitești",$output);
     $output = str_replace("Iasi","Iași",$output);
     $output = str_replace("Targu Mures","Târgu Mureș",$output);
     $output = str_replace("Cluj Napoca","Cluj-Napoca",$output);

     
     
     
   
  return $output;
 }

$method = 'POST';
$server = 'http://zimbor.go.ro/solr/';
$core  = 'shaqodoon';
$command ='/update';
$qs = '?_=1617366504771&commitWithin=1000&overwrite=true&wt=json';

$url =  $server.$core.$command.$qs;
 
$data = file_get_contents('php://input');



$json = json_decode($data);
   
foreach ($json as $item) {
    $item->job_title=html_entity_decode($item->job_title);
    $item->city = city_fix($item->city);
    
}

$data = json_encode($json);

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