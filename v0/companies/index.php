<?php
header("Access-Control-Allow-Origin: *");

require_once '../config.php';

$core = "jobs";

$qs = '?';
$qs = $qs . 'facet.field=company_str';
$qs = $qs . '&';
$qs = $qs . 'facet.limit=100000';
$qs = $qs . '&';
$qs = $qs . 'facet=true';
$qs = $qs . '&';
$qs = $qs . 'fl=company';
$qs = $qs . '&';
$qs = $qs . 'indent=true';
$qs = $qs . '&';
$qs = $qs . 'q.op=OR';
$qs = $qs . '&';
$qs = $qs . 'q=*%3A*';
$qs = $qs . '&';
$qs = $qs . 'rows=0';
$qs = $qs . '&';
$qs = $qs . 'start=0';
$qs = $qs . '&';
$qs = $qs . 'useParams=';

$url = 'http://' . $server . '/solr/' . $core . '/select' . $qs;

$string = file_get_contents($url);
$json = json_decode($string, true);

$companies = $json['facet_counts']['facet_fields']['company_str'];

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
