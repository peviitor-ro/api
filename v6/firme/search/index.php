<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["error" => "Only GET method is allowed"]);
    exit;
}

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

loadEnv('../../../api.env');

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

$id = isset($_GET['id']) ? htmlspecialchars($_GET['id']) : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$rows = isset($_GET['rows']) ? max(1, min((int)$_GET['rows'], 100)) : 10;

if (empty($id)) {
    http_response_code(400);
    echo json_encode(["error" => "Missing required query parameter: id"]);
    exit;
}

$start = ($page - 1) * $rows;

$queryString = http_build_query([
    'q' => "id:$id",
    'wt' => 'json',
    'omitHeader' => 'true',
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
    $response = [
        'docs' => $resultArray['response']['docs'] ?? [],
        'pagination' => [
            'page' => $page,
            'rows' => $rows,
            'total' => $resultArray['response']['numFound'] ?? 0
        ]
    ];
    echo json_encode($response);
}
?>
