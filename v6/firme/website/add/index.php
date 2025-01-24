
text/x-generic index.php ( PHP script, ASCII text, with CRLF line terminators )
<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Adjust your Solr server configuration settings
$server = 'zimbor.go.ro';
$core = 'firme';
$command = '/update';

$qs = '?commit=true&wt=json'; // Commit changes immediately and request JSON response

$url = "http://$server/solr/$core$command$qs";

// Get PUT data
$putdata = fopen("php://input", "r");
$raw_data = '';
while ($data = fread($putdata, 1024)) {
    $raw_data .= $data;
}
fclose($putdata);

$data = json_decode($raw_data);
$id = isset($data->id) ? htmlspecialchars($data->id) : null;
$website = isset($data->website) ? htmlspecialchars($data->website) : null;

// Check if the `id` or `website` is missing
if (empty($id) || empty($website)) {
    http_response_code(400); // Bad Request
    echo json_encode(["error" => "Missing required fields 'id' or 'website'."]);
    exit;
}

// Preparing data for updating the Solr document
$item = new stdClass();
$item->id = $id;
$item->website = ['add' => $website];  // Use 'add' operation for the multiValued field

$json_data = json_encode([$item]);

$options = array(
    'http' => array(
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => $json_data
    )
);

$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);

if ($result === FALSE) {
    http_response_code(500); // Internal Server Error
    echo json_encode(["error" => "Failed to connect to Solr"]);
} else {
    $response = json_decode($result, true);
    if (isset($response['error'])) {
        http_response_code(500);
        echo json_encode(["error" => $response['error']['msg']]);
    } else {
        echo json_encode(["success" => "Website successfully added/updated in Solr"]);
    }
}
?>
