<?php
header("Access-Control-Allow-Origin: *");

function get_master_server(){
    $method = 'GET';
    $server = "https://api.peviitor.ro/";
    $core  = 'v0';
    $command ='/server/';
    $qs = '';
    $url =  $server.$core.$command.$qs;
   
    $options = array(
        'http' => array(
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'GET',
            'content' => $data
        )
    );
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    if ($result === FALSE) { /* Handle error */ }
    $json = json_decode($result);
    foreach($json as $item)
        {
            if ($item->status=="up"){
                return $item->server;
                break;
            }
        }
}

$qs = $_SERVER['QUERY_STRING'];
$qs = urldecode($qs);

if (isset($_GET['user']))
  {
$user = $_GET['user'];
$user = urlencode($user);

$server = 'http://zimbor.go.ro/solr/';
$url = $server . 'auth/select?'.'omitHeader=true&q.op=OR&q=id%3A'.$user;
$json = file_get_contents($url);
$json = json_decode($json);
unset($json->response->docs[0]->_version_);
echo json_encode($json->response->docs[0]);
  }
?>
