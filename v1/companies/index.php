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

$server = 'zimbor.go.ro';
$core = 'shaqodoon';
$command = '/select';
$qs = '?';
$qs = $qs . 'facet.field=company_str';
$qs = $qs . '&';
$qs = $qs . 'facet.limit=10000&facet=true';
$qs = $qs . '&';
$qs = $qs . 'facet=true';
$qs = $qs . '&';
$qs = $qs . 'fl=company';
$qs = $qs . '&';
$qs = $qs . 'facet.sort=index';
$qs = $qs . '&';
$qs = $qs . 'q.op=OR';
$qs = $qs . '&';
$qs = $qs . 'q=';
$qs = $qs . urlencode('*:*');
$qs = $qs . '&';
$qs = $qs . 'rows=0';
$qs = $qs . '&';
$qs = $qs . 'start=0';

$url = 'http://' . $server . '/solr/' . $core . $command . $qs;
//$url = 'https://api.peviitor.ro/v0/search/?facet.field=company_str&facet.limit=10000&facet=true&fl=company&facet.sort=index&indent=true&q.op=OR&q=*%3A*&rows=0&start=0';
$string = file_get_contents($url);
$json = json_decode($string, true);

$companies = $json['facet_counts']['facet_fields']['company_str'];

$results =  new stdClass();
$results->total = count($companies) / 2;
$results->companies = array();

for ($i = 0; $i < count($companies) / 2; $i++) {
   $k = 2 * $i;
   $l = 2 * $i + 1;
   $obj = new stdClass();
   $obj->name = $companies[$k];
   if (isset($_GET['count']))
      if ($_GET['count'] == 'true') {
         $obj->jobs = $companies[$l];
      }
   $results->companies[$i] = new stdClass();
   $results->companies[$i] = $obj;
}
echo json_encode($results);
