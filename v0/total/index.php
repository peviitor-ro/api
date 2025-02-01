<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');

require_once '../config.php';

$core = "jobs";

$qs = '?';
$qs .= 'facet.field=company_str';
$qs .= '&';
$qs .= 'facet.limit=2000000';
$qs .= '&';
$qs .= 'facet=true';
$qs .= '&';
$qs .= 'fl=company';
$qs .= '&';
$qs .= 'indent=true';
$qs .= 'q.op=OR';
$qs .= '&';
$qs .= 'q=*%3A*';
$qs .= '&';
$qs .= 'rows=0';
$qs .= '&';
$qs .= 'start=0';
$qs .= '&';
$qs .= 'useParams=';

$url = 'http://' . $server . '/solr/' . $core . '/select' . $qs;

$string = file_get_contents($url);
$json = json_decode($string, true);

$companies = $json['facet_counts']['facet_fields']['company_str'];

// Count companies with at least one job
$companyCount = 0;
for ($i = 1; $i < count($companies); $i += 2) {
    if ($companies[$i] > 0) {
        $companyCount++;
    }
}

$obj = new stdClass();
$obj->total = new stdClass();
$obj->total->jobs = ''.$json['response']['numFound'];
$obj->total->companies = ''.$companyCount;

echo json_encode($obj);
?>