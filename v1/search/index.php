<?php
header("Access-Control-Allow-Origin: *");
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
     *                name="country",  example="România",
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


if (isset($_GET['q'])) {$q  = "q=".urlencode($_GET['q']);} else {$q='q=*:*';}
if (isset($_GET['company'])) {$q .= "&fq=company:".urlencode($_GET['company']);}
if (isset($_GET['city'])) {$q .= "&fq=city:".urlencode($_GET['city']);}
if (isset($_GET['country'])) {$q .= "&fq=country:".urlencode($_GET['country']);}
if (isset($_GET['page'])) {
    $start = $_GET['page'];
    $start = ($start-1)*10; 
    $q .= "&start=".$start;
}

//$url =  'http://solr.peviitor.ro/solr/shaqodoon/select?'.$qs;
$url =  'http://23.97.216.44/solr/shaqodoon/select?'.$q;
 
 
$json = file_get_contents($url);


echo $json;



?>