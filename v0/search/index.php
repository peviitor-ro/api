<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');

require_once '../config.php';

class SolrQueryBuilder {
    public static function replaceSpaces($string) {
        return str_replace([' ', '&', '$'], ['%20', '%26', '%24'], $string);
    }

    public static function buildParamQuery($param, $queryName) {
        $arrayParams = explode(',', $param);
        $queries = array_map(function ($item) use ($queryName) {
            return $queryName . '%3A%22' . self::replaceSpaces($item) . '%22';
        }, $arrayParams);

        return '&fq=' . implode('%20OR%20', $queries);
    }

    public static function normalizeString($str) {
        $charMap = [
            'ă' => 'a', 'î' => 'i', 'â' => 'a', 'ș' => 's', 'ț' => 't',
            'Ă' => 'A', 'Î' => 'I', 'Â' => 'A', 'Ș' => 'S', 'Ț' => 'T'
        ];
        return strtr($str, $charMap);
    }
}

// Normalizează parametrii din $_GET
foreach ($_GET as $key => $value) {
    $_GET[$key] = SolrQueryBuilder::normalizeString($value);
}

try {
    $core = 'jobs';
    $baseUrl = 'http://' . $server . '/solr/' . $core . '/select';

    // Construim query string-ul
    $query = '?indent=true&q.op=OR&';
    $query .= isset($_GET['q']) ? 'q=' . SolrQueryBuilder::replaceSpaces($_GET['q']) : 'q=*:*';
    $query .= isset($_GET['company']) ? SolrQueryBuilder::buildParamQuery($_GET['company'], 'company') : '';
    $query .= isset($_GET['city']) ? SolrQueryBuilder::buildParamQuery($_GET['city'], 'city') : '';
    $query .= isset($_GET['remote']) ? SolrQueryBuilder::buildParamQuery($_GET['remote'], 'remote') : '&q=remote%3A%22remote%22';

    if (isset($_GET['page'])) {
        $start = ($_GET['page'] - 1) * 12;
        $query .= "&start=$start&rows=12";
    }

    $query .= '&useParams=';
    $url = $baseUrl . $query;

    // Verificăm disponibilitatea endpoint-ului
    $headers = @get_headers($url);
    if ($headers === false || strpos($headers[0], '200') === false) {
        throw new Exception('Endpoint-ul nu este disponibil');
    }

    // Obținem datele din Solr
    $json = file_get_contents($url);
    $jobs = json_decode($json, true);

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
?>
