<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        "error" => "Only POST method is allowed",
        "code" => 405
    ]);
    exit;
}

$contentType = $_SERVER["CONTENT_TYPE"] ?? $_SERVER["HTTP_CONTENT_TYPE"] ?? '';
if (stripos($contentType, 'application/json') === false) {
    http_response_code(415);
    echo json_encode([
        "error" => "Content-Type must be application/json",
        "code" => 415
    ]);
    exit;
}

require_once __DIR__ . '/../../../util/loadEnv.php';
loadEnv(__DIR__ . '/../../../api.env');

$PROD_SERVER = trim(getenv('PROD_SERVER') ?: '');
$SOLR_USER = trim(getenv('SOLR_USER') ?: '');
$SOLR_PASS = trim(getenv('SOLR_PASS') ?: '');

function city_fix($in) {
    $output = $in;
    $output = str_replace("Bucharest", "București", $output);
    $output = str_replace("Brasov", "Brașov", $output);
    $output = str_replace("Timisoara", "Timișoara", $output);
    $output = str_replace("Pitesti", "Pitești", $output);
    $output = str_replace("Iasi", "Iași", $output);
    $output = str_replace("Targu Mures", "Târgu Mureș", $output);
    $output = str_replace("Cluj Napoca", "Cluj-Napoca", $output);
    return $output;
}

function normalizeLocation($location) {
    if (is_array($location)) {
        return array_map(function($loc) {
            return city_fix(trim($loc));
        }, $location);
    }
    if (is_string($location) && !empty($location)) {
        return [city_fix(trim($location))];
    }
    return null;
}

function normalizeTags($tags) {
    if (is_array($tags)) {
        return array_map(function($tag) {
            return strtolower(trim($tag));
        }, array_filter($tags));
    }
    return null;
}

function postJson(string $url, string $payload, ?string $user = null, ?string $pass = null): array {
    $headers = [];
    if ($user && $pass) {
        $headers[] = "Authorization: Basic " . base64_encode("$user:$pass");
    }
    $headers[] = "Content-Type: application/json";
    $context = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => implode("\r\n", $headers),
            'content' => $payload,
            'timeout' => 10
        ]
    ]);
    $data = @file_get_contents($url, false, $context);
    if ($data === false) {
        $err = error_get_last()['message'] ?? 'Unknown error';
        throw new Exception("FETCH FAILED: $url | $err");
    }
    $json = json_decode($data, true);
    if (!is_array($json)) {
        throw new Exception("Invalid JSON response");
    }
    return $json;
}

try {
    if (!$PROD_SERVER) {
        throw new Exception("PROD_SERVER not set");
    }

    $raw_data = file_get_contents("php://input");
    $data = json_decode($raw_data, true);

    if (!isset($data['jobs']) || !is_array($data['jobs'])) {
        http_response_code(400);
        echo json_encode([
            "error" => "Missing or invalid 'jobs' array in payload",
            "code" => 400
        ]);
        exit;
    }

    $items = [];
    foreach ($data['jobs'] as $job) {
        $url = isset($job['url']) ? htmlspecialchars($job['url']) : null;
        $title = isset($job['title']) ? htmlspecialchars($job['title']) : null;
        $company = isset($job['company']) ? htmlspecialchars($job['company']) : null;

        if (!$url || !$title || !$company) {
            continue;
        }

        $item = new stdClass();
        $item->url = trim($url);
        $item->title = trim($title);
        $item->company = trim($company);

        if (isset($job['cif'])) {
            $item->cif = htmlspecialchars(trim($job['cif']));
        }

        if (isset($job['location'])) {
            $item->location = normalizeLocation($job['location']);
        }

        if (isset($job['tags'])) {
            $item->tags = normalizeTags($job['tags']);
        }

        if (isset($job['workmode'])) {
            $item->workmode = htmlspecialchars(trim($job['workmode']));
        }

        if (isset($job['date'])) {
            $item->date = htmlspecialchars(trim($job['date']));
        }

        if (isset($job['status'])) {
            $item->status = htmlspecialchars(trim($job['status']));
        }

        $items[] = $item;
    }

    if (empty($items)) {
        http_response_code(400);
        echo json_encode([
            "error" => "No valid jobs found in payload (url, title, company required)",
            "code" => 400
        ]);
        exit;
    }

    $core = 'job';
    $url = "http://$PROD_SERVER/solr/$core/update?commitWithin=1000&overwrite=true&wt=json";
    $payload = json_encode($items);

    error_log("SCRAPER UPLOAD URL: $url | COUNT: " . count($items));

    $response = postJson($url, $payload, $SOLR_USER, $SOLR_PASS);

    echo json_encode([
        "success" => "Jobs successfully uploaded to Solr",
        "count" => count($items)
    ]);

} catch (Exception $e) {
    error_log("SCRAPER UPLOAD FAILED: " . $e->getMessage());
    http_response_code(503);
    echo json_encode([
        'error' => 'Job core unavailable',
        'details' => $e->getMessage()
    ]);
}
