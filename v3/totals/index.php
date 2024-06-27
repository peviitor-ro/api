<?php
header("Access-Control-Allow-Origin: *");
$qs = "indent=true&q.op=OR&q=logo%3A*&rows=10000&omitHeader=true&useParams=";

function get_server(){
    //get the IP of the server
    //we need a config file to know where is the SOLR
    require('../../_config/index.php');
    return $server;
}

  function get_master_server(){
      // File path
        $filePath = "../server/test/server.txt";

        // Read the contents of the file into a string
           $fileContents = file_get_contents($filePath);

            if ($fileContents !== false) {$server = $fileContents;
                     } else {  $server = 'https://solr.peviitor.ro/solr/';
                             }
                       return $server;
                                }




$results =  new stdClass();
$zimbor = 'http://zimbor.go.ro/solr/';
$prodcore = 'jobs';
$qs0 = '/select?';
$qs = $qs0 . 'facet.field=company_str&facet.limit=10000&facet=true&fl=company&facet.sort=index&indent=true&q.op=OR&q=*%3A*&rows=0&start=0';

$url = $zimbor.$prodcore.$qs;
$string = file_get_contents($url);
$json = json_decode($string, true);

$companies = $json['facet_counts']['facet_fields']['company_str'];

$results->companies = count($companies)/2;
$results->jobs=array();

$qs = $qs0 . 'indent=true&q.op=OR&q=country%3ARom%C3%A2nia&rows=0&useParams=';
$url = $zimbor.$prodcore.$qs;
$string = file_get_contents($url);
$json = json_decode($string, true);

$companies = $json['response']['numFound'];

$results->jobs["ro"] = $companies;


$qs = $qs0 . 'indent=true&q.op=OR&q=*%3A*&rows=0&useParams=';

$url = $url = $zimbor.$prodcore.$qs;
$string = file_get_contents($url);
$json = json_decode($string, true);

$companies = $json['response']['numFound'];

$results->jobs["all"] = $companies;
echo json_encode($results);
?>
