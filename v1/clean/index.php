<?php
header("Access-Control-Allow-Origin: *");
/**
 * 
 * @OA\Post(
 *     path="/v1/clean/", 
 *     tags={"scraper"},
 *     @OA\RequestBody(
 *         @OA\MediaType(
 *             mediaType="application/x-www-form-urlencoded",
 *             @OA\Schema(
 *                 @OA\Property(
 *                     property="hiringOrganization.name",
 *                     type="string"
 *                 ),
 *                 example="hiringOrganization.name=Endava"
 *                      )
 *                  )
 *          ),
    
 *     @OA\Response(response="200", description="Success")
 * )
 */




function company_exist($company)
{
    $method = 'GET';
    $server = 'zimbor.go.ro:8985';
    $core  = 'jobs';
    
    $qs = '?';
    $qs = $qs . 'indent=true&q.op=OR';
    $qs = $qs . '&';
    $qs = $qs . 'q=';
    $qs = $qs . 'company';
    $qs = $qs . urlencode(':"');
    $qs = $qs . $company;
    $qs = $qs . urlencode('"');
    $qs = $qs . '&rows=0&useParams=';

    $url = 'http://' . $server . '/solr/' . $core . '/select' . $qs;
    $string = file_get_contents($url);
    $json = json_decode($string, true);

    if ($json['response']['numFound'] == 0) {
        return "new";
    }
    if ($json['response']['numFound'] != 0) {
        return "existing";
    }
}

function discord_webhook($msg)
{

    $msg .= ' CLEAN in TEST at ' . date("l d-m-Y H:i:s");
    $method = 'POST';
    $url = "https://discord.com/api/webhooks/1127592366614786118/ZOcdq94sqxO4P8iOIkQdRLG9s_vwgRfg1DFxhybwpHkqyet0QTe33rQ7bSDS5AG5HP8n";
    $data = '{"content": "' . $msg . '"}';

    $options = array(
        'http' => array(
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'POST',
            'content' => $data
        )
    );
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    if ($result === FALSE) { /* Handle error */
    }
}

$server = 'zimbor.go.ro:8985';
$method = 'POST';
$core  = 'jobs';
$command = '/update';

$qs = '?';
$qs = $qs . '_=1617366504771';
$qs = $qs . '&';
$qs = $qs . 'commitWithin=1000';
$qs = $qs . '&';
$qs = $qs . 'overwrite=true';
$qs = $qs . '&';
$qs = $qs . 'wt=json';

$url = 'http://' . $server . '/solr/' . $core . $command . $qs;

$company = $_POST['company'];
$data = "{'delete': {'query': 'hiringOrganization.name:" . $company . "'}}";

$options = array(
    'http' => array(
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => $data
    )
);
$msg = '';
if (company_exist($company) == "new") {
    $msg .= "!!! COMPLETELY NEW  !!!  ";
}
$msg .= $company;
discord_webhook($msg);
$context  = stream_context_create($options);

$result = file_get_contents($url, false, $context);
if ($result === FALSE) { /* Handle error */
}
