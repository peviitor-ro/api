<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: *');
header('Content-Type: application/json; charset=utf-8');

// Ensure the request is GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["error" => "Only GET method is allowed"]);
    exit;
}

// Load variables from the api.env file
require_once __DIR__ . '/../../includes/loadEnv.php';
loadEnv(__DIR__ . '/../../api.env');

// Retrieve SOLR variables from environment
$server = getenv('LOCAL_SERVER') ?: ($_SERVER['LOCAL_SERVER'] ?? null);
$username = getenv('SOLR_USER') ?: ($_SERVER['SOLR_USER'] ?? null);
$password = getenv('SOLR_PASS') ?: ($_SERVER['SOLR_PASS'] ?? null);

// Debugging: Check if the server is set
if (!$server) {
    die(json_encode(["error" => "LOCAL_SERVER is not set in api.env"]));
}

$core = 'jobs';

try {
    class StringNormalizer
    {
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

    // Verifică prezența parametrului 'q'
    if (!isset($_GET['q']) || empty(trim($_GET['q']))) {
        http_response_code(400);
        echo json_encode(['message' => 'No query provided']);
        exit;
    }
    $query = StringNormalizer::normalizeString(trim($_GET['q']));

    // Construiește URL-ul
    $url = "http://" . $server . "/solr/" . $core . "/suggest?suggest=true&suggest.build=true&suggest.dictionary=jobTitleSuggester&suggest.q=" . urlencode($query) . "&wt=json";
    $url .= "&suggest.cfq=job_title:\"" . urlencode($query) . "\"";
    $url .= "&suggest.count=100"; // Marim numarul de sugestii returnate

    // Inițializează cURL
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    // Execută request-ul
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    // Verifică dacă există o eroare la cURL
    if ($response === false) {
        http_response_code(503);
        echo json_encode([
            "error" => "SOLR server in DEV is down or unreachable",
            "details" => $curl_error,
            "code" => 503
        ]);
        exit;
    }

    // Verifică dacă Solr a răspuns cu un cod valid
    if ($http_code !== 200) {
        http_response_code($http_code);
        echo json_encode([
            "error" => "Solr request failed",
            "http_code" => $http_code
        ]);
        exit;
    }

    // Decodifică JSON-ul primit
    $jsonArray = json_decode($response, true);

    if (empty($jsonArray['suggest']['jobTitleSuggester'][$query]['suggestions'])) {
        http_response_code(404);
        echo json_encode(['message' => 'No suggestions found']);
        exit;
    }

    // Extrage sugestiile
    $suggestions = $jsonArray['suggest']['jobTitleSuggester'][$query]['suggestions'];
    $filteredSuggestions = [];
    
    foreach ($suggestions as $suggestion) {
        // Verificam daca sugestia contine intregul query (case insensitive)
        $normalizedTerm = StringNormalizer::normalizeString($suggestion['term']);
        if (stripos($normalizedTerm, $query) !== false) {
            $filteredSuggestions[] = $suggestion;
        }
    }
    
    if (empty($filteredSuggestions)) {
        http_response_code(404);
        echo json_encode(['message' => 'No suggestions found']);
        exit;
    }

    echo json_encode(['suggestions' => $filteredSuggestions], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage(), 'code' => $e->getCode()]);
    exit;
}
?>
