
text/x-generic index.php ( PHP script, ASCII text, with CRLF line terminators )
<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Define Solr server settings
$server = 'zimbor.go.ro';
$core = 'firme';
$command = '/update';

$qs = '?commit=true&wt=json'; // Commit changes immediately and request JSON response

$url = "http://$server/solr/$core$command$qs";

// Get DELETE data; Usually, data isn't sent in the body of a DELETE request, so use query parameters or URL segments
$input = file_get_contents("php://input");
$data = json_decode($input, true);
$id = isset($data['id']) ? htmlspecialchars($data['id']) : null;

// Check if the `id` is provided
if (empty($id)) {
    http_response_code(400); // Bad Request
    echo json_encode(["error" => "Missing required field 'id'."]);
    exit;
}

// Preparing data for removing the 'website' field from the document
$item = new stdClass();
$item->id = $id;
$item->website = ['set' => null]; // Set 'website' to null to remove it

$json_data = json_encode([$item]);

// Setting HTTP context options for the POST request to Solr
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
        echo json_encode(["success" => "Website field successfully deleted from the document"]);
    }
}
?>
