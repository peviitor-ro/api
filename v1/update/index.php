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

 
 function get_server(){
    //get the IP of the server
    //we need a config file to know where is the SOLR
    require('../../_config/index.php');
    return $server;
}

 function discord_webhook($msg) {
    $msg .= ' UPDATE in TEST '.date("l d-m-Y H:i:s");
    $method = 'POST';
    $url = "https://discord.com/api/webhooks/1127592366614786118/ZOcdq94sqxO4P8iOIkQdRLG9s_vwgRfg1DFxhybwpHkqyet0QTe33rQ7bSDS5AG5HP8n";
    $data = '{"content": "'.$msg.'"}';

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
    
 }
$method = 'POST';
$server = get_server();
$core  = 'shaqodoon';
$command ='/update';
$qs = '?_=1617366504771&commitWithin=1000&overwrite=true&wt=json';


$data = file_get_contents('php://input');

$json = json_decode($data);
print_r($data);
   
foreach ($json as $item) {
    $item->job_title=html_entity_decode($item->job_title);
    $item->city = city_fix($item->city);
    $item->id = md5($item->job_link)."";
    $company= $item->company;
}

$data = json_encode($json);

$options = array(
    'http' => array(
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => $data
    )
);
discord_webhook($company);
$context  = stream_context_create($options);

foreach ($server as $solrurl){
$url =  $solrurl.$core.$command.$qs;
$result = file_get_contents($url, false, $context);
if ($result === FALSE) { /* Handle error */ }
}

?>
