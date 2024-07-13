<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

$method = 'GET';

require_once '../config.php';

$core  = 'auth';

$qs = '?';
$qs = $qs . 'omitHeader=true';
$qs = $qs . '&';
$qs = $qs . 'q.op=OR';
$qs = $qs . '&';
$qs = $qs . 'q=id%3A';   

$data = file_get_contents('php://input');
$data = json_decode($data);

if (isset($data[0]->id))
{
    $user = $data[0]->id;
    $user = urlencode($user);

    $url = 'http://' . $server . '/solr/' . $core . '/select' . $qs . $user;

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

    $qs = '?';
    $qs = $qs . '_=1617366504771';
    $qs = $qs . '&';
    $qs = $qs . 'commitWithin=1000';
    $qs = $qs . '&';
    $qs = $qs . 'overwrite=true';
    $qs = $qs . '&';
    $qs = $qs . 'wt=json';

    $data ="[".json_encode($json->response->docs[0])."]" ;

    $options = array(
        'http' => array(
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'POST',
            'content' => $data
        )
    );

    $context  = stream_context_create($options);

    $url =  'http://' . $server . $core . $command . $qs;
    
    $result = file_get_contents($url, false, $context);

    if ($result === FALSE) { /* Handle error */ }
        
    echo $data;
}

?>
