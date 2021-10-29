<?php
header("Access-Control-Allow-Origin: *");

   /**
     * @OA\Get(
     *     path="/v1/companies/", tags={"UI"},
   *  @OA\Parameter(
     *                in="query", 
     *                 name="count",
     *                 @OA\Schema(
     *                   type="string"), 
     *                 example="true"
     * ),
     *     @OA\Response(response="200", description="Success")
     * )
     */

$url = 'https://api.peviitor.ro/v0/search/?facet.field=company&facet=on&q=*%3A*&rows=0';
$string = file_get_contents($url);
$json = json_decode($string, true);

$companies = $json['facet_counts']['facet_fields']['company'];

$results =  new stdClass();
$results->total = count($companies)/2;
$results->companies = array();

for($i=0;$i<count($companies)/2;$i++) {
    $k=2*$i;
    $l=2*$i+1;
    $obj = new stdClass();
    $obj->name = $companies[$k];   
    if (isset($_GET['count']))
     if ($_GET['count']=='true')
       {
    $obj->jobs = $companies[$l];
       }
    $results->companies[$i] = new stdClass();
    $results->companies[$i] = $obj;
   
}





echo json_encode($results);


?>