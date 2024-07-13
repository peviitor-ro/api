<?php
header("Access-Control-Allow-Origin: *");

function city_fix($in){
    $output = $in;
    $output = str_replace("Bucharest","București",$output);
    $output = str_replace("Brasov","Brașov",$output);
    $output = str_replace("Timisoara","Timișoara",$output);
    $output = str_replace("Pitesti","Pitești",$output);
    $output = str_replace("Iasi","Iași",$output);
    $output = str_replace("Targu Mures","Târgu Mureș",$output);
    $output = str_replace("Cluj Napoca","Cluj-Napoca",$output);

    return $output;
}

function discord_webhook($msg) {
    $msg .= ' UPDATE in TEST '.date("l d-m-Y H:i:s");
    $method = 'POST';
    $url = "https://discord.com/api/webhooks/1127592366614786118/ZOcdq94sqxO4P8iOIkQdRLG9s_vwgRfg1DFxhybwpHkqyet0QTe33rQ7bSDS5AG5HP8n";
    $data = '{"content": "'.$msg.'"}';

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
}

$method = 'POST';

require_once '../config.php';

$core  = 'jobs';
$command ='/update';

$qs = '?';
$qs = $qs . '_=1617366504771';
$qs = $qs . '&';
$qs = $qs . 'commitWithin=1000';
$qs = $qs . '&';
$qs = $qs . 'overwrite=true';
$qs = $qs . '&';
$qs = $qs . 'wt=json';

$url = 'http://' . $server . $core . $command . $qs;

$data = file_get_contents('php://input');

$json = json_decode($data);
print_r($data);
   
foreach ($json as $item) {
    $item->job_title=html_entity_decode($item->job_title);
    $item->city = city_fix($item->city);
    $item->id = md5($item->job_link)."";
    $company= $item->company;
}

$data = json_encode($json);

$options = array(
    'http' => array(
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => $data
    )
);

discord_webhook($company);
$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);

if ($result === FALSE) { /* Handle error */ }

?>