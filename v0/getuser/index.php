<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: *');
header('Content-Type: application/json; charset=utf-8');

if (isset($_GET['ID'])) {
  $user = $_GET['ID'];
  $user = urlencode($user);

  require_once '../config.php';

  $core = 'auth';

  $qs = '?';
  $qs = $qs . 'omitHeader=true';
  $qs = $qs . '&';
  $qs = $qs . 'q.op=OR';
  $qs = $qs . '&';
  $qs = $qs . 'q=id%3A';

  $url = 'http://' . $server . '/solr/' . $core . '/select' . $qs . $user;

  $string = @file_get_contents($url);
  if ($string === FALSE) {
    http_response_code(503);
    echo json_encode([
      "error" => "SOLR server in DEV is down",
      "code" => 503
    ]);
    exit;
  }

  $json = file_get_contents($url);
  $json = json_decode($json);
  unset($json->response->docs[0]->_version_);
  echo json_encode($json->response->docs[0]);
}
