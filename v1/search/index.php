<?php
header("Access-Control-Allow-Origin: *");

function get_server(){
    //get the IP of the server
    //we need a config file to know where is the SOLR
    require('../../_config/index.php');
    return $server;
}


    /**
 * @OA\Get(
 *     path="/v1/search/",
 *     tags={"search engine"},
 *     summary="Search for jobs",
 *     operationId="searchJobs", // Unique operationId for this GET operation
 *     @OA\Parameter(
 *         in="query",
 *         name="q",
 *         description="Search query",
 *         example="front end",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         in="query",
 *         name="company",
 *         description="Company name filter",
 *         example="Endava",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         in="query",
 *         name="city",
 *         description="City filter",
 *         example="Cluj-Napoca",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         in="query",
 *         name="county",
 *         description="County filter",
 *         example="Bihor",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         in="query",
 *         name="country",
 *         description="Country filter",
 *         example="România",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         in="query",
 *         name="remote",
 *         description="Remote job filter",
 *         example="remote",
 *         @OA\Schema(type="string", enum={"remote", "true", "false"})
 *     ),
 *     @OA\Parameter(
 *         in="query",
 *         name="page",
 *         description="Page number",
 *         example="1",
 *         @OA\Schema(type="integer", format="int32", minimum=1)
 *     ),
 *     @OA\Response(
 *         response="200",
 *         description="Successful search results"
 *     )
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

if (isset($_GET['q'])) {$q  = "q=".urlencode($_GET['q']);} else {$q='q=*:*';}
if (isset($_GET['company'])) {$q .= "&fq=company:".urlencode($_GET['company']);}
if (isset($_GET['city'])) {$q .= '&fq=city%3A"'.urlencode($_GET['city']).'"';}
if (isset($_GET['country'])) {$q .= "&fq=country:".urlencode($_GET['country']);}
if (isset($_GET['county'])) {$q .= "&fq=county:".urlencode($_GET['county']);}
if (isset($_GET['remote'])) {$q .= "&fq=remote:".urlencode($_GET['remote']);}
if (isset($_GET['page'])) {
    $start = $_GET['page'];
    $start = ($start-1)*10; 
    $q .= "&start=".$start;
}

$url =  get_master_server().'shaqodoon/select?'.$q;
$json = file_get_contents($url);
echo $json;
?>
