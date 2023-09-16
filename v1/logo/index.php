<?php
header("Access-Control-Allow-Origin: *");
$qs = "indent=true&q.op=OR&q=logo%3A*&rows=10000&omitHeader=true&useParams=";

function get_server(){
    //get the IP of the server
    //we need a config file to know where is the SOLR
    require('../../_config/index.php');
    return $server;
}
$core ="auth";
$url =  get_server()[0].$core.'/select?'.$qs;

$string = file_get_contents($url);
$json = json_decode($string, true);
$companies = $json['response']['docs'];


$results =  new stdClass();
$results->companies = array();
$results->companies = $companies;


$test = array();
foreach($companies as $company) 
{

    $item = strtolower($company["id"]);
    $xurl  =  $company["logo"];
    $url  = $xurl[0];
    $test[$item] = $url;
    
}


$url = 'https://api.peviitor.ro/v0/search/?facet.field=company_str&facet.limit=10000&facet=true&fl=company&facet.sort=index&indent=true&q.op=OR&q=*%3A*&rows=0&start=0';
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
    $obj->logo = $test[strtolower($obj->name)];
    $results->companies[$i] = new stdClass();
    $results->companies[$i] = $obj;
   

    
}

echo json_encode($results);
?>
