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

$url = 'http://zimbor.go.ro/solr/jobs/select?facet.field=company_str&facet=true&facet.limit=100000&indent=true&q.op=OR&useParams=';
$string = file_get_contents($url);
$json = json_decode($string, true);
$companies = $json['facet_counts']['facet_fields']['company_str'];
$results = array();
 
for($i=0;$i<count($companies)/2;$i++) {
    $k=2*$i;
    $results[$i] = $companies[$k];
  }
echo json_encode($results);
?>
