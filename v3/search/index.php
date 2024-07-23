<?php
header("Access-Control-Allow-Origin: *");

$q = '?';
$q .= 'indent=true';
$q .= '&';
$q .= 'q.op=OR';
$q .= '&';

if (isset($_GET['q'])) {$q  .= "q=" . urlencode($_GET['q']);} else {$q .= 'q=*:*';}
if (isset($_GET['company'])) {$q .= "&q=company%3A%22" . urlencode($_GET['company']) . "%22";}
if (isset($_GET['city'])) {$q .= "&q=city%3A%22" . urlencode($_GET['city']) . "%22";
                        //   $q = $q."%20OR%20remote%3Aremote%20OR%20city%3Aall%20OR%20county%3Aall";
                          }
if (isset($_GET['country'])) {$q .= "&q=country%3A%22".urlencode($_GET['country']) . "%22";}
//if (isset($_GET['county'])) {$q .= "&q=county%3A%22".urlencode($_GET['county']). "%22";}
if (isset($_GET['remote'])) {$q .= "&q=remote%3A%22".urlencode($_GET['remote']). "%22";}
if (isset($_GET['page'])) {
    $start = $_GET['page'];
    $start = ($start-1)*12; 
    $q .= "&start=".$start;
   $q .= "&rows=12";
}

$q .= '&';
$q .= 'useParams=';

require_once '../config.php';

$core = 'jobs';

$url =  'http://' . $server . '/solr/' . $core . '/select' . $q;

$json = file_get_contents($url);
echo $json;
?>
