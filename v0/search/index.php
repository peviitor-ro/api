<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["error" => "Only GET method is allowed"]);
    exit;
}

// Load variables from the api.env file
require_once __DIR__ . '/../../util/loadEnv.php';
loadEnv(__DIR__ . '/../../api.env');

// Retrieve SOLR variables from environment
$server = getenv('LOCAL_SERVER') ?: ($_SERVER['LOCAL_SERVER'] ?? null);
$username = getenv('SOLR_USER') ?: ($_SERVER['SOLR_USER'] ?? null);
$password = getenv('SOLR_PASS') ?: ($_SERVER['SOLR_PASS'] ?? null);

if (!$server) {
    die(json_encode(["error" => "LOCAL_SERVER is not set in api.env"]));
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

// Normalize GET parameters
foreach ($_GET as $key => $value) {
    $_GET[$key] = SolrQueryBuilder::normalizeString($value);
}

// Define allowed fields
$optionalFields = ['start', 'rows', 'sort', 'page', 'q', 'company', 'city', 'remote'];
foreach ($_GET as $key => $value) {
    if (!in_array($key, $optionalFields)) {
        http_response_code(400);
        echo json_encode(["error" => "Unknown field: $key"]);
        exit;
    }
}

try {
    $core = 'jobs';
    $baseUrl = 'http://' . $server . '/solr/' . $core . '/select';

    // Build base query
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
        $startParam = $_GET['start'];

        if (!preg_match('/^\d+$/', $startParam)) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid input for 'start'. Must be a non-negative integer."]);
            exit;
        }

        $startInt = (int) $startParam;
        if ($numFound > 0 && $startInt >= $numFound) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid input for 'start'. Must be less than total results ($numFound)."]);
            exit;
        }

        $finalStart = $startInt;
    }

    if (isset($_GET['rows'])) {
        $rowsParam = $_GET['rows'];

        if (!preg_match('/^[1-9]\d*$/', $rowsParam)) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid input for 'rows'. Must be a positive integer."]);
            exit;
        }

        $rowsInt = (int)$rowsParam;

        if ($numFound > 0 && $rowsInt > ($numFound - $finalStart)) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid 'rows' value. It exceeds available results (" . ($numFound - $finalStart) . ")."]);
            exit;
        }

        $finalRows = $rowsInt;
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

    // Step 3: Handle 'sort' parameter safely
    if (isset($_GET['sort'])) {
        $sortValue = trim($_GET['sort']);

        // Validate format: "field asc" or "field desc"
        if (!preg_match('/^([a-zA-Z0-9_]+)\s+(asc|desc)$/i', $sortValue, $matches)) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid 'sort' format. Expected 'field asc' or 'field desc'."]);
            exit;
        }

        $field = strtolower($matches[1]);
        $direction = strtolower($matches[2]);

        // Whitelisted sortable fields
        $allowedSortFields = ['id', 'title', 'company', 'city', 'date', 'salary'];
        $allowedDirections = ['asc', 'desc'];

        if (!in_array($field, $allowedSortFields) || !in_array($direction, $allowedDirections)) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid 'sort' parameter. Field or direction not allowed."]);
            exit;
        }

        // Append safe sort clause
        $query .= '&sort=' . urlencode("$field $direction");
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
    // Fallback API in case Solr fails
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
