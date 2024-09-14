<?php
header("Access-Control-Allow-Origin: *");

require_once '../config.php';

$core = 'auth';

$qs = '?';
$qs .= 'indent=true';
$qs .= '&';
$qs .= 'q.op=OR';
$qs .= '&';
$qs .= 'q=logo%3A*';
$qs .= '&';
$qs .= 'rows=1000000';
$qs .= '&';
$qs .= 'omitHeader=true';
$qs .= '&';
$qs .= 'useParams=';

$url =  'http://' . $server . '/solr/' . $core . '/select' . $qs;

$string = file_get_contents($url);
$json = json_decode($string, true);
$companies = $json['response']['docs'];

$results =  new stdClass();
$results->companies = array();
$results->companies = $companies;

$test = array();
foreach ($companies as $company) {
    $item = strtolower($company["id"]);
    $xurl  =  $company["logo"];
    $url  = $xurl[0];
    $test[$item] = $url;
}

$core = 'jobs';

$qs = '?';
$qs .= 'facet.field=company_str';
$qs .= '&';
$qs .= 'facet.limit=10000';
$qs .= '&';
$qs .= 'facet=true';
$qs .= '&';
$qs .= 'fl=company';
$qs .= '&facet.sort=index';
$qs .= '&';
$qs .= 'indent=true';
$qs .= '&';
$qs .= 'q.op=OR';
$qs .= '&';
$qs .= 'q=*%3A*';
$qs .= '&';
$qs .= 'rows=0';
$qs .= '&';
$qs .= 'start=0';

$url = 'http://' . $server . '/solr/' . $core . '/select' . $qs;

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
    $obj->logo = $test[strtolower($obj->name)];
    $results->companies[$i] = new stdClass();
    $results->companies[$i] = $obj;
}
echo json_encode($results);
