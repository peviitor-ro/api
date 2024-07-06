<?php
header("Access-Control-Allow-Origin: *");

$server = 'zimbor.go.ro';
$core = "jobs";
     
$qs = '?';
$qs = $qs . 'facet.field=company_str';
$qs = $qs . '&';
$qs = $qs . 'facet=true';
$qs = $qs . '&';
$qs = $qs . 'facet.limit=10000';
$qs = $qs . '&';
$qs = $qs . 'fl=company';
$qs = $qs . '&';
$qs = $qs . 'indent=true';
$qs = $qs . '&';
$qs = $qs . 'q.op=OR';
$qs = $qs . '&';
$qs = $qs . 'useParams=';
         
$url = 'http://' . $server . '/solr/' . $core . '/select'. $qs;

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