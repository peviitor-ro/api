<?php
header("Access-Control-Allow-Origin: *");

if (isset($_GET['user']))
{
  $user = $_GET['user'];
  $user = urlencode($user);

  $server = 'zimbor.go.ro:8985';
  $core = 'auth';

  $qs = '?';
  $qs = $qs . 'omitHeader=true';
  $qs = $qs . '&';
  $qs = $qs . 'q.op=OR';
  $qs = $qs . '&';
  $qs = $qs . 'q=id%3A';

  $url = 'http://' . $server . '/solr/' . $core . '/select'. $qs . $user;

  $json = file_get_contents($url);
  $json = json_decode($json);
  unset($json->response->docs[0]->_version_);
  echo json_encode($json->response->docs[0]);
}

?>