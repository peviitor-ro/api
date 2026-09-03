<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["error" => "Only GET method is allowed"]);
    exit;
}

class SolrQueryBuilder
{
    public static function replaceSpaces($string)
    {
        return str_replace([' ', '&', '$'], ['%20', '%26', '%24'], $string);
    }

    public static function buildParamQuery($param, $queryName)
    {
        $arrayParams = explode(',', $param);
        $queries = array_map(function ($item) use ($queryName) {
            return $queryName . '%3A%22' . self::replaceSpaces($item) . '%22';
        }, $arrayParams);

        return '&fq=' . implode('%20OR%20', $queries);
    }

    public static function normalizeString($str)
    {
        $charMap = [
            'ă' => 'a',
            'î' => 'i',
            'â' => 'a',
            'ș' => 's',
            'ț' => 't',
            'Ă' => 'A',
            'Î' => 'I',
            'Â' => 'A',
            'Ș' => 'S',
            'Ț' => 'T'
        ];
        return strtr($str, $charMap);
    }
}

// Load api.env file
require_once __DIR__ . '/../../../util/loadEnv.php';
loadEnv(__DIR__ . '/../../../api.env');

// Retrieve SOLR variables from environment

$SOLR_SERVER = trim(getenv('SOLR_SERVER') ?: '');
$PROTOCOL = trim(getenv('PROTOCOL') ?: '');
$SOLR_USER = trim(getenv('SOLR_USER') ?: '');
$SOLR_PASS = trim(getenv('SOLR_PASS') ?: '');
$back = trim(getenv('BACK_SERVER') ?: ($_SERVER['BACK_SERVER'] ?? null));

// Debugging: Check if the server is set
if (!$SOLR_SERVER) {
    die(json_encode(["error" => "PROD_SERVER is not set in api.env"]));
}

$context = stream_context_create([
    'http' => [
        'header' => "Authorization: Basic " . base64_encode("$SOLR_USER:$SOLR_PASS")
    ]
]);

// Define specific Solr core and endpoint
$core = 'company';
$selectEndpoint = '/select';

// Retrieve the 'id' from the query parameter
$id = isset($_GET['id']) ? htmlspecialchars($_GET['id']) : '';

if (empty($id)) {
    http_response_code(400); // Bad Request
    echo json_encode(["error" => "Missing required query parameter: id"]);
    exit;
}

// Construct the URL to query the Solr server
$queryString = http_build_query([
    'q' => "id:$id",
    'wt' => 'json',
    'omitHeader' => 'true' // Try to simplify the output format
]);

$url = "$PROTOCOL://" . $SOLR_SERVER . "/solr/$core$selectEndpoint?$queryString";

// Perform the GET request
$result = file_get_contents($url,false,$context);
if ($result === FALSE) {
    http_response_code(500); // Internal Server Error
    echo json_encode(["error" => "Failed to retrieve data from Solr"]);
} else {
    $resultArray = json_decode($result, true);
    
    // Manually strip out the responseHeader if needed
    if(isset($resultArray['response']['docs'])) {
        $docs = array_map(function ($doc) {
        unset($doc['_version_'], $doc['_root_']);
        return $doc;
    }, $resultArray['response']['docs']);

    echo json_encode($docs);
    } else {
        echo json_encode([]);
    }
}
?>
