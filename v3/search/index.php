<?php
header("Access-Control-Allow-Origin: *");

$q = '?';
$q .= 'indent=true';
$q .= '&';
$q .= 'q.op=OR';
$q .= '&';

function replaceSpaces($string) {
  $withoutSpaces = str_replace(' ', '%20', $string);
  $withoutAmpersand = str_replace('&', '%26', $withoutSpaces);
  $withoutDollar = str_replace('$', '%24', $withoutAmpersand);
  
  return $withoutDollar;
}

// title query
if (isset($_GET['q'])) {$q  .= "q=" . replaceSpaces($_GET['q']);} else {$q .= 'q=*:*';}

// company query
if (isset($_GET['company'])) {$q .= "&fq=company%3A%22" . replaceSpaces($_GET['company']) . "%22";}

// city query
if (isset($_GET['city'])) {$q .= "&fq=city%3A%22" . replaceSpaces($_GET['city']) . "%22";}

// county query
//if (isset($_GET['county'])) {$q .= "&q=county%3A%22".urlencode($_GET['county']). "%22";}

// country query but is deprecated
if (isset($_GET['country'])) {$q .= "&q=country%3A%22".urlencode($_GET['country']) . "%22";}

// remote query
if (isset($_GET['remote'])) {$q .= "&fq=remote%3A%22".urlencode($_GET['remote']). "%22";} else {$q .= "&q=remote%3A%22remote%22";}


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