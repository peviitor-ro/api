<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');

require_once '../utils/env.php';

loadEnv('../../api.env');

$server = getenv('PROD_SERVER') ?: ($_SERVER['PROD_SERVER'] ?? null);
$username = getenv('SOLR_USER') ?: ($_SERVER['SOLR_USER'] ?? null);
$password = getenv('SOLR_PASS') ?: ($_SERVER['SOLR_PASS'] ?? null);
$backup = getenv('BACK_SERVER') ?: ($_SERVER['BACK_SERVER'] ?? null);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["error" => "Only GET method is allowed"]);
    exit;
}

// Fetch JSON and throw error if failed
function fetchJson(string $url, $context = null): array
{
    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        throw new Exception("Failed to fetch URL: $url");
    }

    $json = json_decode($response, true);
    if ($json === null) {
        throw new Exception("Invalid JSON from URL: $url");
    }

    return $json;
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

    /**
     * Escape Solr special characters to prevent query injection and parsing errors.
     * Special characters: + - && || ! ( ) { } [ ] ^ " ~ * ? : \ /
     * 
     * @param string $value The value to escape
     * @return string The escaped value
     */
    public static function escapeSolrSpecialChars($value)
    {
        // Escape Solr special characters with backslash
        // In character class, only need to escape: \ - ] and special regex chars
        $pattern = '/([\+\-&|!(){}\[\]^"~*?:\\\\\/ ])/';
        return preg_replace($pattern, '\\\\$1', $value);
    }
}

try {
    if (!$server) {
        die(json_encode(["error" => "PROD_SERVER is not set in api.env"]));
    }

    foreach ($_GET as $key => $value) {
        $_GET[$key] = SolrQueryBuilder::normalizeString($value);
    }

    $core = 'jobs';
    $baseUrl = 'http://' . $server . '/solr/' . $core . '/select';
    $query = '?indent=true&q.op=OR&';
    
    // Handle search query with proper escaping and phrase search support
    // - Multi-word searches (e.g. "manual software") use OR-based matching
    // - Quoted searches (e.g. '"exact phrase"') perform phrase matching
    // - Special characters are escaped to prevent Solr query injection
    // Note: For improved multi-word handling, consider using defType=edismax with qf/mm/pf parameters
    if (isset($_GET['q']) && !empty(trim($_GET['q']))) {
        $searchTerm = trim($_GET['q']);
        $termLength = strlen($searchTerm);
        
        // Check if user provided an explicit phrase search (surrounded by quotes)
        $isExplicitPhrase = ($termLength >= 2 && 
                            $searchTerm[0] === '"' && 
                            $searchTerm[$termLength - 1] === '"');
        
        if ($isExplicitPhrase) {
            // Preserve user-intended phrase search
            $query .= 'q=' . rawurlencode($searchTerm);
        } else {
            // Escape Solr special characters for multi-word search
            // The backslash escaping is URL-encoded for transmission, then decoded by Solr
            $escapedTerm = SolrQueryBuilder::escapeSolrSpecialChars($searchTerm);
            $query .= 'q=' . rawurlencode($escapedTerm);
        }
    } else {
        $query .= 'q=*:*';
    }
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
    $countData = fetchJson($baseUrl . $query . "&rows=0", $context);
    $numFound = $countData['response']['numFound'] ?? 0;

    // Step 2: Validate start and rows
    $finalStart = 0;
    $finalRows = 12;

    if (isset($_GET['start'])) {
        if (!ctype_digit($_GET['start']) || ($_GET['start'] < 0 || $_GET['start'] >= $numFound)) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid input for 'start'."]);
            exit;
        }
        $finalStart = intval($_GET['start']);
    }

    if (isset($_GET['rows'])) {
        if (!ctype_digit($_GET['rows']) || ($_GET['rows'] <= 0 || $_GET['rows'] > $numFound - $finalStart)) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid input for 'rows'."]);
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
            echo json_encode(["error" => "Invalid input for 'page'."]);
            exit;
        }
    }

    $query .= "&start=$finalStart&rows=$finalRows";
    $finalUrl = $baseUrl . $query;

    $jobs = fetchJson($finalUrl, $context);

    if (isset($jobs['response']['numFound']) && $jobs['response']['numFound'] == 0) {
        http_response_code(404);
        echo json_encode(["error" => "No jobs found in the database", "code" => 404]);
        exit;
    }

    echo json_encode($jobs);
} catch (Exception $e) {
    // Fallback to backup endpoint
    $backupUrl = $backup . 'mobile/';
    $fallbackQuery = isset($_GET['q']) ? '?search=' . SolrQueryBuilder::replaceSpaces($_GET['q']) : '?search=';
    $fallbackQuery .= isset($_GET['page']) ? '&page=' . $_GET['page'] : '';
    $citiesString = str_replace('~', '', $_GET['city'] ?? '');
    $fallbackQuery .= isset($_GET['city']) ? '&cities=' . $citiesString : '';
    $fallbackQuery .= isset($_GET['company']) ? '&companies=' . SolrQueryBuilder::replaceSpaces($_GET['company']) : '';
    $fallbackQuery .= isset($_GET['remote']) ? '&remote=' . SolrQueryBuilder::replaceSpaces($_GET['remote']) : '';

    try {
        $json = fetchJson($backupUrl . $fallbackQuery);
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
        }, $json['results'] ?? []);

        $response = [
            'response' => [
                'docs' => $newJobs,
                'numFound' => $json['count'] ?? 0
            ]
        ];

        echo json_encode($response);
    } catch (Exception $fallbackEx) {
        http_response_code(500);
        echo json_encode([
            "error" => "Fallback endpoint failed as well.",
            "details" => $fallbackEx->getMessage()
        ]);
    }
}