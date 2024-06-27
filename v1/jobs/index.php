<?php
header("Access-Control-Allow-Origin: *");
/**
 * @OA\Get(
 *     path="/v1/jobs/",
 *     tags={"machine learning"},
 *     operationId="getJobs",
 *     @OA\Parameter(
 *         in="query",
 *         name="start",
 *         @OA\Schema(type="string"),
 *         example="100"
 *     ),
 *     @OA\Response(
 *         response="200",
 *         description="Success"
 *     )
 * )
 */

function get_master_server()
{
    $method = 'GET';
    $server = "https://api.peviitor.ro/";
    $core  = 'v0';
    $command = '/server/';
    $qs = '';
    $url =  $server . $core . $command . $qs;

    $options = array(
        'http' => array(
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'GET',
            'content' => $data
        )
    );
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    if ($result === FALSE) { /* Handle error */
    }
    $json = json_decode($result);
    foreach ($json as $item) {
        if ($item->status == "up") {
            return $item->server;
            break;
        }
    }
}

$qs = "q=*%3A*&rows=100&omitHeader=true";
//$qs = urldecode($qs);
if (isset($_GET["start"])) {
    $start = $_GET["start"];
    $qs .= "&start=" . $start;
}
$url =  get_master_server() . 'shaqodoon/select?' . $qs;
$json = file_get_contents($url);
echo $json;
