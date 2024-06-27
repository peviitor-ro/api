<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");



function get_server(){
        //get the IP of the server
        //we need a config file to know where is the SOLR
        require('../../_config/index.php');
        return $server;
    }

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

$data = file_get_contents('php://input');
$data = json_decode($data);

$server = 'http://zimbor.go.ro/solr/';


if (isset($data[0]->id))
  {
$user = $data[0]->id;
$user = urlencode($user);

$url =  $server.'auth/select?'.'omitHeader=true&q.op=OR&q=id%3A'.$user;
$json = file_get_contents($url);
$json = json_decode($json);
unset($json->response->docs[0]->_version_);

 if (isset($data[0]->id)) {$json->response->docs[0]->id = $data[0]->id;}
 if (isset($data[0]->url)) {$json->response->docs[0]->url = $data[0]->url;}
 if (isset($data[0]->company)) {$json->response->docs[0]->company = $data[0]->company;}
 if (isset($data[0]->logo)) {$json->response->docs[0]->logo = $data[0]->logo;}
 if (isset($data[0]->apikey)) { $json->response->docs[0]->apikey = $data[0]->apikey;}

//print_r ($json->response->docs[0]);



$method = 'POST';

$core  = 'auth';
$command ='/update';
$qs = '?_=1617366504771&commitWithin=1000&overwrite=true&wt=json';

$data ="[".json_encode($json->response->docs[0])."]" ;

$options = array(
    'http' => array(
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => $data
    )
);
$context  = stream_context_create($options);

$server = get_server();
foreach($server as $solrurl){
 $url =  $solrurl.$core.$command.$qs;          
$result = file_get_contents($url, false, $context);
if ($result === FALSE) { /* Handle error */ }
}
    
echo $data;
  }
?>
