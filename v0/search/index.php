<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["error" => "Only GET method is allowed"]);
    exit;
}

// Load variables from the .env file
function loadEnv($file)
{
    $file = realpath($file);

    // Check if the .env file exists
    if (!$file || !file_exists($file)) {
        die(json_encode(["error" => "The .env file does not exist!", "path" => $file]));
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

// Load .env file
loadEnv('../../.env');

// Retrieve SOLR variables from environment
$server = getenv('LOCAL_SERVER') ?: ($_SERVER['LOCAL_SERVER'] ?? null);
$username = getenv('SOLR_USER') ?: ($_SERVER['SOLR_USER'] ?? null);
$password = getenv('SOLR_PASS') ?: ($_SERVER['SOLR_PASS'] ?? null);

// Debugging: Check if the server is set
if (!$server) {
    die(json_encode(["error" => "LOCAL_SERVER is not set in .env"]));
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

// Normalizează parametrii din $_GET
foreach ($_GET as $key => $value) {
    $_GET[$key] = SolrQueryBuilder::normalizeString($value);
}

// Definim câmpurile opționale
$requiredFields = ['q'];
$optionalFields = ['start', 'rows', 'sort'];

foreach ($requiredFields as $field) {
    if (!isset($_GET[$field])) {
        http_response_code(400);
        echo json_encode(["error" => "Missing required field: $field"]);
        exit;
    }
}

//Verificam daca a fost introdus altceva in afara de campurile prestabilite
foreach ($_GET as $key => $value) {
    if (!in_array($key, $requiredFields) && !in_array($key, $optionalFields)) {
        http_response_code(400);
        echo json_encode(["error" => "Unknown field: $key"]);
        exit;
    }
}

try {
    $core = 'jobs';
    $baseUrl = 'http://' . $server . '/solr/' . $core . '/select';

    // Construim query string-ul
    $query = '?indent=true&q.op=OR&';
    $query .= isset($_GET['q']) && !empty(trim($_GET['q']))
        ? ('q=' . rawurlencode('"' . trim($_GET['q']) . '"'))
        : 'q=*:*';
    $query .= isset($_GET['company']) ? SolrQueryBuilder::buildParamQuery($_GET['company'], 'company') : '';
    $query .= isset($_GET['city']) ? SolrQueryBuilder::buildParamQuery($_GET['city'], 'city') : '';
    $query .= isset($_GET['remote']) ? SolrQueryBuilder::buildParamQuery($_GET['remote'], 'remote') : '&q=remote%3A%22remote%22';

    if (isset($_GET['start'])) {
        if (!ctype_digit($_GET['start'])) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid input for the 'start' parameter. It must be a positive integer."]);
            exit;
        }
        $start = $_GET['start'];
        if ($start >= 0 && $start <= 2147483647)
            $query .= "&start=" . $start;
        else {
            http_response_code(400);
            echo json_encode(["error" => "Invalid input for the 'start' parameter. It must be a positive integer."]);
            exit;
        }
    }

    if (isset($_GET['rows'])) {
        if (!ctype_digit($_GET['rows'])) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid input for the 'rows' parameter. It must be a non-negative positive integer."]);
            exit;
        }
        $rows = $_GET['rows'];

        if ($rows > 0 && $rows <= 2147483647)
            $query .= "&rows=" . $rows;
        else {
            http_response_code(400);
            echo json_encode(["error" => "Invalid input for the 'rows' parameter. It must be a non-negative positive integer."]);
            exit;
        }
    }

    $query .= '&useParams=';
    $url = $baseUrl . $query;

    $context = stream_context_create([
        'http' => [
            'header' => "Authorization: Basic " . base64_encode("$username:$password")
        ]
    ]);

    // Fetch data from Solr
    $string = @file_get_contents($url, false, $context);

    // Check if Solr is down (server not responding)
    if ($string == false) {
        http_response_code(503);
        echo json_encode([
            "error" => "SOLR server in DEV is down",
            "code" => 503
        ]);
        exit;
    }

    $jobs = json_decode($string, true);

    if (isset($jobs['response']['numFound']) && $jobs['response']['numFound'] == 0) {
        http_response_code(404);
        echo json_encode([
            "error" => "This job is not in the Database",
            "code" => 404
        ]);
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
