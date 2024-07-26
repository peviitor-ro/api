<?php

require_once '../config.php';

function getLogo($companyName){

  global $server;
  $core = 'auth';

  $params = array(
    'q.op' => 'OR',
    'q' => 'id:"' . strtolower($companyName) . '"',
    'rows' => 1,
    'indent' => 'true',
    'useParams' => ''
  );

  $url =  'http://' . $server . '/solr/' . $core . '/select?' . http_build_query($params);


  $string = file_get_contents($url);
  $json = json_decode($string, true);
  $logoUrl = $json['response']['docs'];


  if (isset($logoUrl[0]['logo'][0])) {
    return $logoUrl[0]['logo'][0];
  } else {

    // get the protocol
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";

    // get the domain
    $domain = $_SERVER['HTTP_HOST'];

    // transform the domain into a url
    $url = $protocol . $domain . '/v3/' . '/images/no-logo.svg';

    return $url;
  }
}
?>