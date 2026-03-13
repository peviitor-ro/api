<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../util/loadEnv.php';
loadEnv(__DIR__ . '/../../api.env');

$LOCAL_SERVER = trim(getenv('LOCAL_SERVER') ?: '');
$SOLR_USER = trim(getenv('SOLR_USER') ?: '');
$SOLR_PASS = trim(getenv('SOLR_PASS') ?: '');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["error" => "Only GET method allowed"]);
    exit;
}

$version = '1.0.0';
$checks = [];
$overallStatus = 'pass';

$startTime = microtime(true);

try {
    if (!$LOCAL_SERVER) {
        throw new Exception("LOCAL_SERVER not set");
    }

    $solrStart = microtime(true);
    $url = "http://$LOCAL_SERVER/solr/job/select?q=*:*&rows=0";
    
    $context = stream_context_create([
        'http' => [
            'method'  => 'GET',
            'header'  => "Authorization: Basic " . base64_encode("$SOLR_USER:$SOLR_PASS"),
            'timeout' => 5
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    $solrTime = round((microtime(true) - $solrStart) * 1000);
    
    if ($response === false) {
        $checks['solr'] = [
            'status' => 'fail',
            'response_time_ms' => $solrTime,
            'message' => 'Connection failed'
        ];
        $overallStatus = 'fail';
    } else {
        $data = json_decode($response, true);
        if (isset($data['responseHeader']['status']) && $data['responseHeader']['status'] === 0) {
            $checks['solr'] = [
                'status' => 'pass',
                'response_time_ms' => $solrTime,
                'message' => 'Connected'
            ];
        } else {
            $checks['solr'] = [
                'status' => 'fail',
                'response_time_ms' => $solrTime,
                'message' => 'Query failed'
            ];
            $overallStatus = 'fail';
        }
    }
} catch (Exception $e) {
    $checks['solr'] = [
        'status' => 'fail',
        'response_time_ms' => 0,
        'message' => $e->getMessage()
    ];
    $overallStatus = 'fail';
}

$totalTime = round((microtime(true) - $startTime) * 1000);

$response = [
    'status' => $overallStatus,
    'version' => $version,
    'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
    'response_time_ms' => $totalTime,
    'checks' => $checks
];

$httpCode = ($overallStatus === 'pass') ? 200 : 503;
http_response_code($httpCode);

echo json_encode($response, JSON_UNESCAPED_UNICODE);
