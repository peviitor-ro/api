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
if (isset($_GET['company'])) {
  $company = explode(',', $_GET['company']);
  if (count($company) > 1) {
    $q .= "&fq=company%3A%22".replaceSpaces($company[0]). "%22";
    for ($i = 1; $i < count($company); $i++) {
      $q .= "%20OR%20company%3A%22".replaceSpaces($company[$i]). "%22";
    }
  } else {
    $q .= "&fq=company%3A%22".replaceSpaces($company[0]). "%22";
  }
}

// city query
if (isset($_GET['city'])) {
  $city = explode(',', $_GET['city']);
  if (count($city) > 1) {
    $q .= "&fq=city%3A%22".replaceSpaces($city[0]). "%22";
    for ($i = 1; $i < count($city); $i++) {
      $q .= "%20OR%20city%3A%22".replaceSpaces($city[$i]). "%22";
    }
  } else {
    $q .= "&fq=city%3A%22".replaceSpaces($city[0]). "%22";
  }
}

// county query
//if (isset($_GET['county'])) {$q .= "&q=county%3A%22".urlencode($_GET['county']). "%22";}

// country query but is deprecated
if (isset($_GET['country'])) {$q .= "&q=country%3A%22".urlencode($_GET['country']) . "%22";}

// remote query
if (isset($_GET['remote'])) {
  $remote = explode(',', $_GET['remote']);
  if (count($remote) > 1) {
    $q .= "&fq=remote%3A%22".replaceSpaces($remote[0]). "%22";
    for ($i = 1; $i < count($remote); $i++) {
      $q .= "%20OR%20remote%3A%22".replaceSpaces($remote[$i]). "%22";
    }
  } else {
    $q .= "&fq=remote%3A%22".replaceSpaces($remote[0]). "%22";
  }
} else {
  $q .= "&q=remote%3A%22remote%22";
}

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