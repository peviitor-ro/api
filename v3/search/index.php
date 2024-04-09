<?php
header("Access-Control-Allow-Origin: *");


if (isset($_GET['q'])) {$q  = "q=".urlencode($_GET['q']);} else {$q='q=*:*';}
if (isset($_GET['company'])) {$q .= "&fq=company:".urlencode($_GET['company']);}
if (isset($_GET['city'])) {$q .= "&fq=city:".urlencode($_GET['city']);
                           $q = $q."%20OR%20remote%3Aremote%20OR%20city%3Aall%20OR%20county%3Aall";
                          }
if (isset($_GET['country'])) {$q .= "&fq=country:".urlencode($_GET['country']);}
//if (isset($_GET['county'])) {$q .= "&fq=county:".urlencode($_GET['county']);}
if (isset($_GET['remote'])) {$q .= "&fq=remote:".urlencode($_GET['remote']);}
if (isset($_GET['page'])) {
    $start = $_GET['page'];
    $start = ($start-1)*10; 
    $q .= "&start=".$start;
   $q .= "&rows=12";
}
$url =  'http://zimbor.go.ro/solr/'.'jobs/select?'.$q;
$json = file_get_contents($url);
echo $json;
?>
