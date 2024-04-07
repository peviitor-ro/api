<?php
header("Access-Control-Allow-Origin: *");
    /**
     * @OA\Get(
     *     path="/v1/background/", tags={"UI"},
     *     @OA\Response(response="200", description="Success")
     * )
     */



$x = date('w');
$x = $x-1;

$url =  'http://zimbor.go.ro/'.'tech/select?'.'q.op=OR&q='."cat:".$x.'&rows=100';
$json = file_get_contents($url);

echo $json;

?>
