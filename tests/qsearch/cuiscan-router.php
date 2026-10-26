<?php
// Local HTTP fixtures based on https://cuiscan.ro/api-docs.html.
// Synthetic company data only; never connects to CUIscan or Solr.
header('Content-Type: application/json');
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$query = $_GET['q'] ?? '';
$log = getenv('QSEARCH_MOCK_LOG');
if ($log) {
    file_put_contents($log, json_encode([
        'event' => 'request', 'method' => $_SERVER['REQUEST_METHOD'],
        'path' => $path, 'q' => $query,
    ]) . PHP_EOL, FILE_APPEND | LOCK_EX);
}
if ($_SERVER['REQUEST_METHOD'] !== 'GET' || $path !== '/api.php' || ($_GET['action'] ?? '') !== 'search') {
    http_response_code(501);
    echo json_encode(['error' => 'Endpoint not modeled']);
    return;
}
$row = fn($cui, string $name = 'ZIRA TEST SRL') => [
    'cui' => $cui, 'name' => $name, 'locality' => 'Cluj-Napoca', 'county' => 'Cluj',
];
switch ($query) {
    case 'failure':
        http_response_code(500);
        header('Content-Type: text/html');
        echo '<html>Unavailable</html>';
        return;
    case 'ratelimit':
        http_response_code(429);
        echo json_encode(['error' => 'Too many requests']);
        return;
    case 'timeout':
        usleep(9500000);
        echo '[]';
        return;
    case 'invalid':
        echo json_encode(['unexpected' => []]);
        return;
    case 'objectempty':
        echo '{}';
        return;
    case 'malformed':
        echo '{broken';
        return;
    case 'badrow':
        $rows = [$row(88000001), ['cui' => 'x', 'name' => 'BAD']];
        break;
    case 'empty':
        $rows = [];
        break;
    case 'pepper events':
        $rows = [$row(88300001, 'SALT & PEPPER EVENTS SRL')];
        break;
    case 'limit':
        $rows = array_map(fn($i) => $row(88100000 + $i, 'LIMIT TEST ' . $i), range(0, 29));
        break;
    case 'software':
        // Live search returned 50 despite a documented maximum of 30.
        $rows = array_map(fn($i) => $row(88200000 + $i, 'SOFTWARE TEST ' . $i), range(0, 49));
        break;
    case 'race':
        $rows = [$row(88000003, 'RACE TEST')];
        break;
    case 'partial':
        $rows = [$row(88000004, 'PARTIAL TEST A'), $row(88000005, 'PARTIAL TEST B')];
        break;
    default:
        $rows = [$row(88000001), $row('RO88000001'), $row(88000002)];
}
echo json_encode($rows);
