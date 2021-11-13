<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");





$data = file_get_contents('php://input');
$data = json_decode($data);




if (isset($data[0]->id))
  {
$user = $data[0]->id;
$user = urlencode($user);

$url =  'http://23.97.216.44/solr/auth/select?'.'omitHeader=true&q.op=OR&q=id%3A'.$user;
$json = file_get_contents($url);
$json = json_decode($json);
unset($json->response->docs[0]->_version_);

 if (isset($data[0]->id)) {$json->response->docs[0]->id = $data[0]->id;}
 if (isset($data[0]->url)) {$json->response->docs[0]->url = $data[0]->url;}
 if (isset($data[0]->company)) {$json->response->docs[0]->company = $data[0]->company;}
 if (isset($data[0]->apikey)) { $json->response->docs[0]->apikey = $data[0]->apikey;}

//print_r ($json->response->docs[0]);



$method = 'POST';
$server = 'http://23.97.216.44/solr/';
$core  = 'auth';
$command ='/update';
$qs = '?_=1617366504771&commitWithin=1000&overwrite=true&wt=json';

$url =  $server.$core.$command.$qs;
$data ="[".json_encode($json->response->docs[0])."]" ;





$options = array(
    'http' => array(
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => $data
    )
);
$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);
if ($result === FALSE) { /* Handle error */ }

echo $data;


  }


?>