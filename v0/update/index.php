<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, OPTIONS");
header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

function city_fix($in)
{
    $output = $in;
    $output = str_replace("Bucharest", "București", $output);
    $output = str_replace("Brasov", "Brașov", $output);
    $output = str_replace("Timisoara", "Timișoara", $output);
    $output = str_replace("Pitesti", "Pitești", $output);
    $output = str_replace("Iasi", "Iași", $output);
    $output = str_replace("Targu Mures", "Târgu Mureș", $output);
    $output = str_replace("Cluj Napoca", "Cluj-Napoca", $output);

    return $output;
}

$method = 'PUT';

require_once '../config.php';

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

$job_title = isset($_GET['job_title']) ? htmlspecialchars(trim($_GET['job_title']), ENT_QUOTES, 'UTF-8') : null;
$company = isset($_GET['company']) ? htmlspecialchars(trim($_GET['company']), ENT_QUOTES, 'UTF-8') : null;
$city = isset($_GET['city']) ? htmlspecialchars(trim(city_fix($_GET['city'])), ENT_QUOTES, 'UTF-8') : null;
$job_link = isset($_GET['job_link']) ? filter_var(trim($_GET['job_link']), FILTER_VALIDATE_URL) : null;

// Create data for Solr
$item = new stdClass();
$item->job_title = $job_title;
$item->company = $company;
$item->city = $city;
$item->job_link = $job_link;

$data = json_encode([$item]);

$options = array(
    'http' => array(
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => $data
    )
);

$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);

if ($result === FALSE) {
    http_response_code(500); // Internal Server Error
    echo json_encode(["error" => "Failed to insert data into Solr"]);
    exit;
}

// Return success response
echo $data;
echo json_encode(["success" => "Data successfully inserted into Solr"]);
