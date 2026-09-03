<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
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

if (!$SOLR_SERVER) {
    die(json_encode(["error" => "PROD_SERVER is not set in api.env"]));
}

$context = stream_context_create([
    'http' => [
        'header' => "Authorization: Basic " . base64_encode("$SOLR_USER:$SOLR_PASS")
    ]
]);

$core = 'company';
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

$url = "$PROTOCOL://$SOLR_SERVER/solr/$core$selectEndpoint?$queryString";

$result = file_get_contents($url, false, $context);

if ($result === FALSE) {
    http_response_code(500);
    echo json_encode(["error" => "Failed to retrieve data from Solr"]);
} else {
    $resultArray = json_decode($result, true);
    if (isset($resultArray['response']['docs'])) {
        $docs = array_map(function ($doc) {
            unset($doc['_version_'], $doc['_root_']);
            return $doc;
        }, $resultArray['response']['docs']);

        $response = [
            'pagination' => [
                'page' => $page,
                'rows' => $rows,
                'total' => $resultArray['response']['numFound'],
                'total_pages' => ceil($resultArray['response']['numFound'] / $rows)
            ],
            'docs' => $docs
        ];
        echo json_encode($response);
    } else {
        echo json_encode([
            'pagination' => [
                'page' => $page,
                'rows' => $rows,
                'total' => 0,
                'total_pages' => 0
            ],
            'docs' => []
        ]);
    }
}
