<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["error" => "Only GET method is allowed"]);
    exit;
}

// Load variables from the api.env file
function loadEnv($file)
{
    $file = realpath($file);
    if (!$file || !file_exists($file)) {
        die(json_encode(["error" => "The api.env file does not exist!", "path" => $file]));
    }

    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
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
            'ă' => 'a', 'î' => 'i', 'â' => 'a', 'ș' => 's', 'ț' => 't',
            'Ă' => 'A', 'Î' => 'I', 'Â' => 'A', 'Ș' => 'S', 'Ț' => 'T'
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

if (!$server) {
    die(json_encode(["error" => "PROD_SERVER is not set in api.env"]));
}

$context = stream_context_create([
    'http' => [
        'header' => "Authorization: Basic " . base64_encode("$username:$password")
    ]
]);

$core = 'firme';
$selectEndpoint = '/select';

// Retrieve and validate query parameters
$query = isset($_GET['q']) ? htmlspecialchars(urldecode($_GET['q'])) : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$rows = isset($_GET['rows']) ? max(1, min((int)$_GET['rows'], 100)) : 10; // Limit max rows to 100

if (empty($query)) {
    http_response_code(400);
    echo json_encode(["error" => "Missing required query parameter: q"]);
    exit;
}

// Calculate start position for Solr (0-based index)
$start = ($page - 1) * $rows;

// Construct the URL with pagination parameters
$queryString = http_build_query([
    'q.op' => 'OR',
    'q' => "\"$query\"",
    'useParams' => '',
    'omitHeader' => 'false', // Changed to false to get total count
    'wt' => 'json',
    'start' => $start,
    'rows' => $rows
]);

$url = "http://$server/solr/$core$selectEndpoint?$queryString";

$result = file_get_contents($url, false, $context);

if ($result === FALSE) {
    http_response_code(500);
    echo json_encode(["error" => "Failed to retrieve data from Solr"]);
} else {
    $resultArray = json_decode($result, true);
    if (isset($resultArray['response']['docs'])) {
        // Prepare response with pagination metadata
        $response = [
            'docs' => $resultArray['response']['docs'],
            'pagination' => [
                'page' => $page,
                'rows' => $rows,
                'total' => $resultArray['response']['numFound'],
                'total_pages' => ceil($resultArray['response']['numFound'] / $rows)
            ]
        ];
        echo json_encode($response);
    } else {
        echo json_encode([
            'docs' => [],
            'pagination' => [
                'page' => $page,
                'rows' => $rows,
                'total' => 0,
                'total_pages' => 0
            ]
        ]);
    }
}
?>
