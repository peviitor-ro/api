<?php
header('Content-Type: application/json');

$uri  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$rawQ = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
parse_str($rawQ ?? '', $params);
$q    = $params['q'] ?? '';
$rows = (int)($params['rows'] ?? 0);

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
