<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: *');

if (isset($_GET['user']))
{
  $user = $_GET['user'];
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

  $json = file_get_contents($url);
  $json = json_decode($json);
  unset($json->response->docs[0]->_version_);
  echo json_encode($json->response->docs[0]);
}

?>