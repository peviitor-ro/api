<?php
header("Access-Control-Allow-Origin: *");

   /**
     * @OA\Get(
     *     path="/v1/cities/", tags={"UI"},
     *           @OA\Parameter(
     *                in="query", 
     *                 name="count",
     *                 @OA\Schema(
     *                   type="string"),   
     *                 example="true"
     *                        ),
     *     @OA\Response(response="200", description="Success")
     * )
     */

$url = 'https://api.peviitor.ro/v0/search/?facet.field=city&facet=on&q=*%3A*&rows=0';
$string = file_get_contents($url);
$json = json_decode($string, true);

$cities = $json['facet_counts']['facet_fields']['city'];

$results =  new stdClass();
$results->total = count($cities)/2;
$results->cities = array();

for($i=0;$i<count($cities)/2;$i++) {
    $k=2*$i;
    $l=2*$i+1;
    $obj = new stdClass();
    $obj->name = $cities[$k];   
    if (isset($_GET['count']))
     if ($_GET['count']=='true')
       {
    $obj->jobs = $cities[$l];
       }
    $results->cities[$i] = new stdClass();
    $results->cities[$i] = $obj;
   
    
}





echo json_encode($results);


?>