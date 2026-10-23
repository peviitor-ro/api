<?php
header('Content-Type: application/json');

$uri  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$rawQ = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
parse_str($rawQ ?? '', $params);
$q    = $params['q'] ?? '';
$rows = (int)($params['rows'] ?? 0);

// v7: core 'apikeys' (keys, panel authorizations, login tokens) is a small stateful store in a temp file,
// so the panel flow (token created, then consumed; key created, then listed) works.
// POST /mock/reset restores the seed: key '1...' active and '2...' revoked for CIF 87654321.
if (preg_match('#/(apikeys)/(get|select|update)#', $uri, $m) || $uri === '/mock/reset') {
    $storeFile = sys_get_temp_dir() . '/pv-mock-store.json';
    $seed = ['apikeys' => [
        hash('sha256', 'pv_live_' . str_repeat('1', 48)) => ['id' => hash('sha256', 'pv_live_' . str_repeat('1', 48)), 'type' => 'key', 'cif' => '87654321', 'company' => 'TEST V7 SRL', 'prefix' => 'pv_live_11111111', 'active' => true, 'created_at' => '2026-01-01T00:00:00Z', '_version_' => 1],
        hash('sha256', 'pv_live_' . str_repeat('2', 48)) => ['id' => hash('sha256', 'pv_live_' . str_repeat('2', 48)), 'type' => 'key', 'cif' => '87654321', 'company' => 'TEST V7 SRL', 'prefix' => 'pv_live_22222222', 'active' => false, 'created_at' => '2026-01-01T00:00:00Z', '_version_' => 1],
    ]];
    if ($uri === '/mock/reset' || !file_exists($storeFile)) {
        file_put_contents($storeFile, json_encode($seed));
        if ($uri === '/mock/reset') {
            echo '{"reset":true}';
            return true;
        }
    }
    $fp = fopen($storeFile, 'c+');
    flock($fp, LOCK_EX);
    $store = json_decode(stream_get_contents($fp), true) ?: $seed;
    [$core, $action] = [$m[1], $m[2]];
    $docs = $store[$core] ?? [];

    if ($action === 'get') {
        echo json_encode(['doc' => $docs[$params['id'] ?? ''] ?? null]);
    } elseif ($action === 'update') {
        $conflict = false;
        foreach (json_decode(file_get_contents('php://input'), true) ?: [] as $doc) {
            $old = $docs[$doc['id']] ?? null;
            if (isset($doc['_version_']) && $doc['_version_'] > 0 && ($old['_version_'] ?? null) !== $doc['_version_']) {
                $conflict = true;
                break;
            }
            $doc['_version_'] = ($old['_version_'] ?? 0) + 1;
            $docs[$doc['id']] = $doc;
        }
        if ($conflict) {
            http_response_code(409);
            echo json_encode(['error' => ['msg' => 'version conflict', 'code' => 409]]);
        } else {
            $store[$core] = $docs;
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode($store));
            echo json_encode(['responseHeader' => ['status' => 0]]);
        }
    } else {
        // Supports the queries the code sends: *:*, field:"value" and field:value joined by AND
        $match = array_values(array_filter($docs, function ($doc) use ($q) {
            if ($q === '*:*') {
                return true;
            }
            foreach (explode(' AND ', $q) as $clause) {
                [$field, $value] = explode(':', $clause, 2);
                if ((string)($doc[$field] ?? '') !== trim(stripslashes($value), '"')) {
                    return false;
                }
            }
            return true;
        }));
        usort($match, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
        echo json_encode(['response' => ['numFound' => count($match), 'docs' => array_slice($match, 0, $rows ?: 10)]]);
    }
    flock($fp, LOCK_UN);
    fclose($fp);
    return true;
}

// v7: jobs of CIF 87654321 (one scraped, one posted through v7) and one job of another company
$v7Jobs = [
    'https://example.com/scraped/1' => ['url' => 'https://example.com/scraped/1', 'title' => 'Inginer Automatist', 'cif' => '87654321', 'location' => ['Timișoara, Romania'], 'status' => 'scraped'],
    'https://example.com/v7/own' => ['url' => 'https://example.com/v7/own', 'title' => 'Tester', 'cif' => '87654321', 'location' => ['Iasi'], 'status' => 'activ', 'source' => 'company', '_version_' => 1],
    'https://example.com/other' => ['url' => 'https://example.com/other', 'title' => 'Contabil', 'cif' => '11111111', 'location' => ['Cluj-Napoca']],
];
if (str_contains($uri, '/job/get')) {
    preg_match_all('/(?:^|&)id=([^&]*)/', file_get_contents('php://input'), $m);
    $docs = array_values(array_filter(array_map(fn($id) => $v7Jobs[urldecode($id)] ?? null, $m[1])));
    echo json_encode(['response' => ['numFound' => count($docs), 'docs' => $docs]]);
    return true;
}
if (str_contains($uri, '/job/select') && str_contains($q, 'cif:"87654321"')) {
    $docs = array_values(array_filter($v7Jobs, fn($d) => $d['cif'] === '87654321'));
    echo json_encode(['response' => ['numFound' => count($docs), 'docs' => $docs]]);
    return true;
}

// v7: writes to the job core. New docs must carry _version_ (-1 = create only). The url
// https://example.com/v7/race is "created by someone else" between the check and the write,
// so Solr answers 409. Delete queries are kept for /mock/last-delete.
$lastDeleteFile = sys_get_temp_dir() . '/pv-mock-last-delete.txt';
if ($uri === '/mock/last-delete') {
    echo json_encode(['query' => @file_get_contents($lastDeleteFile) ?: null]);
    return true;
}
if (str_contains($uri, '/job/update')) {
    $payload = json_decode(file_get_contents('php://input'), true);
    if (isset($payload['delete']['query'])) {
        file_put_contents($lastDeleteFile, $payload['delete']['query']);
    } elseif (is_array($payload) && array_is_list($payload)) {
        foreach ($payload as $doc) {
            if (($doc['source'] ?? null) === 'company' && !isset($doc['_version_'])) {
                http_response_code(400);
                echo json_encode(['error' => ['msg' => 'v7 doc written without _version_', 'code' => 400]]);
                return true;
            }
            if (($doc['_version_'] ?? null) === -1 && $doc['url'] === 'https://example.com/v7/race') {
                http_response_code(409);
                echo json_encode(['error' => ['msg' => "version conflict for {$doc['url']} expected=-1 actual=42", 'code' => 409]]);
                return true;
            }
        }
    }
    echo json_encode(['responseHeader' => ['status' => 0, 'QTime' => 1]]);
    return true;
}

// Handle update (POST, DELETE via atomic update)
if (str_contains($uri, '/update')) {
    echo json_encode(['responseHeader' => ['status' => 0, 'QTime' => 1]]);
    return true;
}

// Company core brand lookup
if (str_contains($uri, '/company/select')) {
    if (str_contains($q, 'ORANGE')) {
        echo json_encode([
            'response' => [
                'numFound' => 1,
                'docs' => [['id' => '12345678', 'company' => 'ORANGE SA']]
            ]
        ]);
    } elseif (str_contains($q, 'GOOGLE')) {
        echo json_encode([
            'response' => [
                'numFound' => 2,
                'docs' => [
                    ['id' => '11111111', 'company' => 'GOOGLE SRL'],
                    ['id' => '22222222', 'company' => 'GOOGLE ROMANIA SA']
                ]
            ]
        ]);
    } else {
        echo json_encode(['response' => ['numFound' => 0, 'docs' => []]]);
    }
    return true;
}

// Job core
if (str_contains($q, '*:*')) {
    if ($rows > 0) {
        echo json_encode([
            'response' => [
                'numFound' => 42,
                'docs' => [[
                    'title'     => 'Inginer IT',
                    'company'   => 'NUME SRL',
                    'location'  => ['București'],
                    'workmode'  => 'remote',
                    'url'       => 'https://example.com/job/1',
                    'salary'    => '5000-8000 RON',
                    'tags'      => ['python'],
                    'cif'       => '12345678',
                    'date'      => '2026-06-15T10:00:00Z',
                    'status'    => 'published'
                ]]
            ]
        ]);
    } else {
        echo json_encode(['response' => ['numFound' => 42]]);
    }
    return true;
}

if (str_contains($q, 'company:"NUME SRL"') || str_contains($q, 'cif:12345678')) {
    echo json_encode(['response' => ['numFound' => 5]]);
} elseif (str_contains($q, 'company:"UNKNOWN"')) {
    echo json_encode(['response' => ['numFound' => 0]]);
} elseif (str_contains($q, 'cif:11111111') || str_contains($q, 'cif:22222222')) {
    echo json_encode(['response' => ['numFound' => 3]]);
} else {
    echo json_encode(['response' => ['numFound' => 0]]);
}
return true;
