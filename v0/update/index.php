<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, OPTIONS");
header('Content-Type: application/json; charset=utf-8');
// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Ensure the request is PUT
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["error" => "Only PUT method is allowed"]);
    exit;
}

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

$putdata = fopen("php://input", "r");
$raw_data = '';
while ($data = fread($putdata, 1024)) {
    $raw_data .= $data;
}
fclose($putdata);

$data = json_decode($raw_data);
$job_title = isset($data->job_title) ? htmlspecialchars($data->job_title) : null;
$company = isset($data->company) ? htmlspecialchars($data->company) : null;
$city = isset($data->city) ? htmlspecialchars($data->city) : null;
$job_link = isset($data->job_link) ? htmlspecialchars($data->job_link ) : null;

if (!$job_title || !$company || !$city || !$job_link) {
    http_response_code(400);
    echo json_encode(["error" => "Missing required fields: job_title, company, city, or job_link"]);
    exit;
}

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
echo json_encode(["success" => "Data successfully inserted into Solr"]);
