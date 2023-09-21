<?php
header("Access-Control-Allow-Origin: *");

function get_master_server(){
// File path
$filePath = "../../v0/server/test/server.txt";

// Read the contents of the file into a string
$fileContents = file_get_contents($filePath);

if ($fileContents !== false) {
       $server = $fileContents;
} else {
    $server = 'https://solr.peviitor.ro/solr/';
}
    return $server;
}

    


if (isset($_GET['q'])) {$q  = "q=".urlencode($_GET['q']);} else {$q='q=*:*';}
if (isset($_GET['company'])) {$q .= "&fq=company:".urlencode($_GET['company']);}
if (isset($_GET['city'])) {$q .= '&fq=city%3A"'.urlencode($_GET['city']).'"';}
if (isset($_GET['country'])) {$q .= "&fq=country:".urlencode($_GET['country']);}
if (isset($_GET['remote'])) {$q .= "&fq=remote:".urlencode($_GET['remote']);}
if (isset($_GET['page'])) {
    $start = $_GET['page'];
    $start = ($start-1)*10; 
    $q .= "&start=".$start;
}
$url =  get_master_server().'jobs/select?'.$q;
$json = file_get_contents($url);
echo $json;
?>
