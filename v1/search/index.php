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
     *     path="/v1/search/", tags={"search engine"},
     *     
     *          @OA\Parameter(
     *                in="query", 
     *                name="q",  example="front end",
     *               @OA\Schema(type="string")
     * ),
     *               @OA\Parameter(
     *                in="query", 
     *                name="company",  example="Endava",
     *               @OA\Schema(type="string")
     * ),
     *           @OA\Parameter(
     *                in="query", 
     *                name="city",  example="Cluj-Napoca",
     *               @OA\Schema(type="string")
     * ),
     *           @OA\Parameter(
     *                in="query", 
     *                name="county",  example="Bihor",
     *               @OA\Schema(type="string")
     * ),
     *           @OA\Parameter(
     *                in="query", 
     *                name="country",  example="România",
     *               @OA\Schema(type="string")
     * ),
     *           @OA\Parameter(
     *                in="query", 
     *                name="remote",  example="remote",
     *               @OA\Schema(type="string")
     * ),
     *           @OA\Parameter(
     *                in="query", 
     *                name="page",  example="1",
     *               @OA\Schema(type="string")
     * ),
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
