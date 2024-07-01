<?php
//CORS enable
header("Access-Control-Allow-Origin: *");

 /**
     * @OA\Get(
     *     path="/v1/logo/", tags={"logo"},
     *     @OA\Response(response="200", description="Success")
     * )
     */

$server = 'zimbor.go.ro:8985';
$core = "jobs";

$qs = '?';
$qs = $qs . 'q=logo:*';
$qs = $qs . '&';
$qs = $qs . 'q.op=OR';
$qs = $qs . '&';
$qs = $qs . 'indent=true';
$qs = $qs . '&';
$qs = $qs . 'q=*%3A*';
$qs = $qs . '&';
$qs = $qs . 'rows=100000';
$qs = $qs . '&';
$qs = $qs . 'useParams=';

$url = 'http://' . $server . '/solr/' . $core . '/select'. $qs;
     
$string = file_get_contents($url);
$json = json_decode($string, true);

$companies = $json['response']['docs'];

$results =  new stdClass();
$results->total = count($companies);
$results->companies = array();

for($i=0;$i<count($companies);$i++) {
    $obj  = new stdClass();
    
	$obj -> name = $companies[$i]["id"];
	$obj -> logo =$companies[$i]["logo"][0];
    $results->companies[$i] = new stdClass();
    $results->companies[$i] = $obj;
}
echo json_encode($results);
?>
