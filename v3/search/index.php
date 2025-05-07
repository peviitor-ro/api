<?php
header("Access-Control-Allow-Origin: *");
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

try{
    // Load api.env file
loadEnv('../../.env');

// Retrieve SOLR variables from environment
$server = getenv('LOCAL_SERVER') ?: ($_SERVER['LOCAL_SERVER'] ?? null);
$username = getenv('SOLR_USER') ?: ($_SERVER['SOLR_USER'] ?? null);
$password = getenv('SOLR_PASS') ?: ($_SERVER['SOLR_PASS'] ?? null);

// Debugging: Check if the server is set
if (!$server) {
    die(json_encode(["error" => "PROD_SERVER is not set in api.env"]));
}

// Normalize GET parameters
foreach ($_GET as $key => $value) {
    $_GET[$key] = SolrQueryBuilder::normalizeString($value);
}

// Define allowed fields
$optionalFields = ['start', 'rows', 'sort', 'page', 'q'];

foreach ($_GET as $key => $value) {
    if (!in_array($key, $optionalFields)) {
        http_response_code(400);
        echo json_encode(["error" => "Unknown field: $key"]);
        exit;
    }
}

$core = 'jobs';
$baseUrl = 'http://' . $server . '/solr/' . $core . '/select';

$query = '?indent=true&q.op=OR&';
$query .= isset($_GET['q']) && !empty(trim($_GET['q']))
    ? ('q=' . rawurlencode('"' . trim($_GET['q']) . '"'))
    : 'q=*:*'; // Return all jobs if 'q' is missing

$query .= isset($_GET['company']) ? SolrQueryBuilder::buildParamQuery($_GET['company'], 'company') : '';
$query .= isset($_GET['city']) ? SolrQueryBuilder::buildParamQuery($_GET['city'], 'city') : '';
$query .= isset($_GET['remote']) ? SolrQueryBuilder::buildParamQuery($_GET['remote'], 'remote') : '&q=remote%3A%22remote%22';

$query .= '&useParams=';

$context = stream_context_create([
    'http' => [
        'header' => "Authorization: Basic " . base64_encode("$username:$password")
    ]
]);

// Step 1: Get numFound
$countUrl = $baseUrl . $query . "&rows=0";
$countResponse = @file_get_contents($countUrl, false, $context);
if ($countResponse === false) {
    http_response_code(503);
    echo json_encode(["error" => "Failed to fetch count from Solr"]);
    exit;
}

$countData = json_decode($countResponse, true);
$numFound = $countData['response']['numFound'] ?? 0;

// Step 2: Validate start and rows
$finalStart = 0;
$finalRows = 12; // default

if (isset($_GET['start'])) {
    if (!ctype_digit($_GET['start']) || ($_GET['start'] < 0 || $_GET['start'] >= $numFound)) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid input for the 'start' parameter. It must be a positive integer less than $numFound."]);
        exit;
    }
    $finalStart = intval($_GET['start']);
}

if (isset($_GET['rows'])) {
    if (!ctype_digit($_GET['rows']) || ($_GET['rows'] <= 0 || $_GET['rows'] > $numFound - $finalStart)) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid input for the 'rows' parameter. It must be a positive integer less than " . ($numFound - $finalStart) . "."]);
        exit;
    }
    $finalRows = intval($_GET['rows']);
}

if (isset($_GET['page']) && ctype_digit($_GET['page'])) {
    $page = intval($_GET['page']);
    if ($page > 0) {
        $finalStart = ($page - 1) * $finalRows;
        if ($finalStart >= $numFound) $finalStart = 0;
    } else {
        http_response_code(400);
        echo json_encode(["error" => "Invalid input for the 'rows' parameter. It must be a positive integer."]);
        exit;
    }
}

// Append start & rows
$query .= "&start=$finalStart&rows=$finalRows";

// Final Solr URL
$url = $baseUrl . $query;

$string = @file_get_contents($url, false, $context);

if ($string == false) {
    http_response_code(503);
    echo json_encode(["error" => "SOLR server in DEV is down", "code" => 503]);
    exit;
}

$jobs = json_decode($string, true);

if (isset($jobs['response']['numFound']) && $jobs['response']['numFound'] == 0) {
    http_response_code(404);
    echo json_encode(["error" => "No jobs found in the database", "code" => 404]);
    exit;
}

echo json_encode($jobs);

} catch (Exception $e) {
    // Fallback la endpoint-ul de rezervă
    $backupUrl = $backup . '/mobile/';
    $fallbackQuery = isset($_GET['q']) ? '?search=' . SolrQueryBuilder::replaceSpaces($_GET['q']) : '?search=';
   
    $fallbackQuery .= isset($_GET['page']) ? '&page=' . $_GET['page'] : '';
    $citiesString = str_replace('~', '', $_GET['city'] ?? '');
    $fallbackQuery .= isset($_GET['city']) ? '&cities=' . $citiesString : '';
    $fallbackQuery .= isset($_GET['company']) ? '&companies=' . SolrQueryBuilder::replaceSpaces($_GET['company']) : '';
    $fallbackQuery .= isset($_GET['remote']) ? '&remote=' . SolrQueryBuilder::replaceSpaces($_GET['remote']) : '';

    $json = file_get_contents($backupUrl . $fallbackQuery);
    $jobs = json_decode($json, true);

    $newJobs = array_map(function ($job) {
        return [
            'job_title' => $job['job_title'],
            'company' => $job['company_name'],
            'city' => [$job['city']],
            'county' => [$job['county']],
            'remote' => $job['remote'],
            'job_link' => $job['job_link'],
            'id' => $job['id']
        ];
    }, $jobs['results'] ?? []);

    $response = (object)[
        'response' => (object)[
            'docs' => $newJobs,
            'numFound' => $jobs['count'] ?? 0
        ]
    ];

    echo json_encode($response);
}