<?php
header("Access-Control-Allow-Origin: *");

    /**
     * @OA\Get(
     *     path="/v3/jobs/", tags={"machine learning"},
     *           @OA\Parameter(
     *                in="query", 
     *                 name="start",  
     *  @OA\Schema(
     *                   type="string"), 
     * example="100"
     * ),
     * 
     *     @OA\Response(response="200", description="Success")
     * )
     */

$method = 'GET';
$server = 'zimbor.go.ro';
$core  = 'jobs';

$qs = 'q=*%3A*';
$qs = $qs . '&';
$qs = $qs . 'rows=100';
$qs = $qs . '&';
$qs = $qs . 'omitHeader=true';

$url =  'http://' . $server . '/solr/' . '/select?' . $qs;

if (isset($_GET["start"])) {$start=$_GET["start"];$qs.="&start=".$start;}

$json = file_get_contents($url);

echo $json;

?>