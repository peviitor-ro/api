<?php
header("Access-Control-Allow-Origin: *");


require_once './getLogo.php';

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

function buildParamQuery($param, $queryName) {
  $arrayParams = explode(',', $param);
  if (count($arrayParams) > 1) {
     $query = "&fq=" .$queryName ."%3A%22" . replaceSpaces($arrayParams[0]) . "%22";
      for ($i = 1; $i < count($arrayParams); $i++) {
          $query .= "%20OR%20". $queryName ."%3A%22" . replaceSpaces($arrayParams[$i]) . "%22";
      }
  } else {
      $query = "&fq=". $queryName ."%3A%22" . replaceSpaces($arrayParams[0]) . "%22";
  }
  return $query;
}

if (isset($_GET['q'])) {$q  .= "q=" . replaceSpaces($_GET['q']);} else {$q .= 'q=*:*';}

// company query
if (isset($_GET['company'])) {
  $q .= buildParamQuery($_GET['company'],'company');
}

// city query
if (isset($_GET['city'])) {
  $q .= buildParamQuery($_GET['city'], 'city');
}
// county query
//if (isset($_GET['county'])) {$q .= "&q=county%3A%22".urlencode($_GET['county']). "%22";}

// country query but is deprecated
if (isset($_GET['country'])) {$q .= "&q=country%3A%22".urlencode($_GET['country']) . "%22";}

// remote query
if (isset($_GET['remote'])) {
  $q .= buildParamQuery($_GET['remote'],'remote');
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
$jobs = json_decode($json, true);

for ($i = 0; $i < count($jobs['response']['docs']); $i++) {
  $company = $jobs['response']['docs'][$i]['company'];

  $logo = getLogo($company[0]);
  $jobs['response']['docs'][$i]['logoUrl'] = $logo;
}

echo json_encode($jobs);
?>