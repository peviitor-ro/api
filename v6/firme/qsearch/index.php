<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header('Content-Type: application/json; charset=utf-8');



if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["error" => "Only GET method is allowed"]);
    exit;
}


// Load variables from the api.env file
function loadEnv($file)
{
    $file = realpath($file);

    // Check if the api.env file exists
    if (!$file || !file_exists($file)) {
        die(json_encode(["error" => "The api.env file does not exist!", "path" => $file]));
    }

    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments (lines starting with '#')
        if (strpos(trim($line), '#') === 0) continue;

        // Split the line into key and value, and add to environment
        list($key, $value) = explode('=', $line, 2) + [NULL, NULL];
        if ($key && $value) {
            $key = trim($key);
            $value = trim($value);
            $_SERVER[$key] = $value;
            putenv("$key=$value");
        }
    }
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
loadEnv('../../../api.env');

// Retrieve SOLR variables from environment
$server = getenv('PROD_SERVER') ?: ($_SERVER['PROD_SERVER'] ?? null);
$username = getenv('SOLR_USER') ?: ($_SERVER['SOLR_USER'] ?? null);
$password = getenv('SOLR_PASS') ?: ($_SERVER['SOLR_PASS'] ?? null);
$back = getenv('BACK_SERVER') ?: ($_SERVER['BACK_SERVER'] ?? null);

// Debugging: Check if the server is set
if (!$server) {
    die(json_encode(["error" => "PROD_SERVER is not set in api.env"]));
}




$context = stream_context_create([
    'http' => [
        'header' => "Authorization: Basic " . base64_encode("$username:$password")
    ]
]);


// Define Solr core and the endpoint for selecting documents
$core = 'firme';
$selectEndpoint = '/select';

// Retrieve the 'q' query parameter from the URL, handle URL encoding, and check if it's provided
$query = isset($_GET['q']) ? htmlspecialchars(urldecode($_GET['q'])) : '';

// Respond with an error if the query parameter is missing
if (empty($query)) {
    http_response_code(400); // Bad Request
    echo json_encode(["error" => "Missing required query parameter: q"]);
    exit;
}

// Construct the URL to query the Solr server including necessary Solr query parameters
$queryString = http_build_query([
    'q.op' => 'OR',   // Use OR operator for queries
    'q' => "\"$query\"",
    'useParams' => '',
    'omitHeader' => 'true',
    'wt' => 'json',
]);

// Formulate the full URL using the server setting from config.php
$url = "http://$server/solr/$core$selectEndpoint?$queryString";

// Execute the GET request to the Solr server
$result = file_get_contents($url,false,$context);

// Handle possible errors in the GET request or send back results
if ($result === FALSE) {
    http_response_code(500); // Internal Server Error
    echo json_encode(["error" => "Failed to retrieve data from Solr"]);
} else {
    $resultArray = json_decode($result, true);
    if (isset($resultArray['response']['docs'])) {
        echo json_encode($resultArray['response']['docs']);
    } else {
        echo json_encode([]);
    }
}
?>
