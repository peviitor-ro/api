<?php
// Standalone isolated HTTP integration test: php tests/integration/TestCompanyQsearch.php
require_once __DIR__ . '/../helpers.php';
registerTestFile(__FILE__);
$root = dirname(__DIR__, 2);
$temp = sys_get_temp_dir() . '/peviitor-qsearch-' . bin2hex(random_bytes(8));
mkdir($temp . '/v1/firme/qsearch/lib', 0777, true);
mkdir($temp . '/util');
foreach (['v1/firme/qsearch/index.php', 'v1/firme/qsearch/lib/search.php', 'v1/swagger.json', 'util/loadEnv.php'] as $path) {
    copy($root . '/' . $path, $temp . '/' . $path);
}
$processes = [];
function qsearchPort(): int {
    $socket = stream_socket_server('tcp://127.0.0.1:0');
    $port = (int)substr(strrchr(stream_socket_get_name($socket, false), ':'), 1);
    fclose($socket); return $port;
}
function qsearchCall(string $url, string $method = 'GET'): array {
    $body = @file_get_contents($url, false, stream_context_create(['http' => ['method' => $method, 'ignore_errors' => true, 'timeout' => 15]]));
    preg_match('/\s(\d{3})\s/', $http_response_header[0] ?? '', $match);
    return [(int)($match[1] ?? 0), json_decode($body ?: '', true)];
}
function qsearchStart(array $command, string $log, string $cwd, ?array $env = null) {
    $proc = proc_open($command, [0 => ['pipe', 'r'], 1 => ['file', $log, 'a'], 2 => ['file', $log, 'a']], $pipes, $cwd, $env);
    if (!is_resource($proc)) { throw new RuntimeException('Could not start local test server'); }
    fclose($pipes[0]); return $proc;
}
try {
    $apiPort = qsearchPort(); $mockPort = qsearchPort(); $solrPort = qsearchPort();
    file_put_contents($temp . '/api.env', "SOLR_SERVER=127.0.0.1:$solrPort\nPROTOCOL=http\nCUISCAN_API_URL=http://127.0.0.1:$mockPort/api.php\n");
    $env = getenv();
    $env['QSEARCH_MOCK_STORE'] = $temp . '/store.json';
    $env['QSEARCH_MOCK_LOG'] = $temp . '/cuiscan-requests.ndjson';
    $processes[] = qsearchStart([PHP_BINARY, '-S', "127.0.0.1:$mockPort", $root . '/tests/qsearch/cuiscan-router.php'], $temp . '/cuiscan.log', $root, $env);
    $processes[] = qsearchStart([PHP_BINARY, '-S', "127.0.0.1:$solrPort", $root . '/tests/qsearch/solr-router.php'], $temp . '/solr.log', $root, $env);
    $processes[] = qsearchStart([PHP_BINARY, '-S', "127.0.0.1:$apiPort", '-t', $temp], $temp . '/api.log', $root);
    foreach ([$mockPort, $solrPort, $apiPort] as $port) {
        $ready = false;
        for ($i = 0; $i < 100; $i++) {
            $socket = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.1);
            if ($socket) { fclose($socket); $ready = true; break; }
            usleep(100000);
        }
        if (!$ready) { throw new RuntimeException("Test server $port failed. Logs: $temp"); }
    }
    $base = "http://127.0.0.1:$apiPort/v1/firme/qsearch/";
    $reset = fn() => qsearchCall("http://127.0.0.1:$solrPort/reset", 'POST');
    if (in_array('--serve', $argv, true)) {
        $reset();
        echo "Postman baseUrl: http://127.0.0.1:$apiPort\n";
        echo "Swagger specification: http://127.0.0.1:$apiPort/v1/swagger.json\n";
        echo "Sandbox only: temporary API and database; CUIscan responses are mocked.\n";
        echo "Press Enter to stop the sandbox.\n";
        fgets(STDIN);
        return;
    }
    test('invalid input rejected before external calls', function () use ($base) {
        foreach (['', '?q=a', '?q[]=zira', '?q=%00zira', '?q=zira&page[]=1', '?q=zira&rows=no'] as $query) {
            assertEqual(400, qsearchCall($base . $query)[0]);
        }
        assertEqual(405, qsearchCall($base . '?q=zira', 'POST')[0]);
    });
    test('imports multiple results, deduplicates CUI and preserves existing metadata', function () use ($base, $reset) {
        $reset(); [$status, $body] = qsearchCall($base . '?q=zira');
        assertEqual(200, $status); assertEqual(2, count($body['docs']));
        assertEqual(3, $body['import']['received']); assertEqual(1, $body['import']['imported']);
        assertEqual(1, $body['import']['existing']);
        assertEqual(['Cluj-Napoca, Cluj'], $body['docs'][1]['location'] ?? $body['docs'][0]['location']);
        $existing = array_values(array_filter($body['docs'], fn($d) => $d['id'] === '88000002'))[0];
        assertEqual('KEEP ME', $existing['brand']); assertTrue(!isset($existing['_version_']));
        [$status, $body] = qsearchCall($base . '?q=zira&page=2&rows=1');
        assertEqual(200, $status); assertEqual(0, $body['import']['imported']);
        assertEqual(2, $body['import']['existing']); assertEqual(2, $body['pagination']['total']);
        assertEqual(1, count($body['docs']));
    });
    test('no matches return an empty successful response', function () use ($base, $reset) {
        $reset(); [$status, $body] = qsearchCall($base . '?q=empty');
        assertEqual(200, $status); assertEqual([], $body['docs']); assertEqual(0, $body['import']['imported']);
    });
    test('concurrent create conflict preserves the winning company', function () use ($base, $reset, $solrPort) {
        $reset(); [$status, $body] = qsearchCall($base . '?q=race');
        assertEqual(200, $status); assertEqual(1, $body['import']['existing']);
        $doc = qsearchCall("http://127.0.0.1:$solrPort/solr/company/get?id=88000003")[1]['doc'];
        assertEqual('PRESERVED', $doc['brand']);
    });
    test('upstream errors, invalid payloads and rate limits make no writes', function () use ($base, $reset, $temp) {
        foreach (['failure' => 502, 'ratelimit' => 503, 'invalid' => 502, 'objectempty' => 502, 'malformed' => 502, 'badrow' => 502] as $q => $expected) {
            $reset(); $before = file_get_contents($temp . '/store.json');
            assertEqual($expected, qsearchCall($base . '?q=' . $q)[0]);
            assertEqual($before, file_get_contents($temp . '/store.json'));
        }
    });
    test('CUI-only or unrelated provider matches are not imported', function () use ($base, $reset) {
        $reset(); [$status, $body] = qsearchCall($base . '?q=nomatch');
        assertEqual(200, $status); assertEqual(0, $body['import']['matched']);
        assertEqual(0, $body['import']['imported']); assertEqual([], $body['docs']);
    });
    test('timeout is bounded and returns 504 without writes', function () use ($base, $reset, $temp) {
        $reset(); $before = file_get_contents($temp . '/store.json'); $start = microtime(true);
        assertEqual(504, qsearchCall($base . '?q=timeout')[0]);
        assertTrue(microtime(true) - $start < 12); assertEqual($before, file_get_contents($temp . '/store.json'));
    });
    test('source cap is explicit, database pagination does not limit import', function () use ($base, $reset) {
        $reset(); [$status, $body] = qsearchCall($base . '?q=limit&rows=2');
        assertEqual(200, $status); assertEqual(30, $body['import']['imported']);
        assertEqual(2, count($body['docs'])); assertTrue($body['import']['possibly_truncated']);
        assertTrue(!$body['import']['source_pagination_supported']); assertTrue(isset($body['warnings']));
    });
    test('multiword name returns the stored company on initial and repeated searches', function () use ($base, $reset) {
        $reset(); [$status, $body] = qsearchCall($base . '?q=pepper%20events');
        assertEqual(200, $status); assertEqual(1, $body['import']['imported']);
        assertEqual(1, $body['pagination']['total']); assertEqual('88300001', $body['docs'][0]['id']);
        [$status, $body] = qsearchCall($base . '?q=pepper%20events');
        assertEqual(200, $status); assertEqual(1, $body['import']['existing']);
        assertEqual(1, $body['pagination']['total']); assertEqual('88300001', $body['docs'][0]['id']);
    });
    test('storage failure reports completed imports and retry remains safe', function () use ($base, $reset) {
        $reset(); [$status, $body] = qsearchCall($base . '?q=partial');
        assertEqual(503, $status); assertEqual(1, $body['import']['imported']);
        [$status, $body] = qsearchCall($base . '?q=partial');
        assertEqual(503, $status); assertEqual(0, $body['import']['imported']); assertEqual(1, $body['import']['existing']);
    });
    test('software response above documented limit imports all 50 and retries safely', function () use ($base, $reset) {
        $reset(); [$status, $body] = qsearchCall($base . '?q=software&rows=10');
        assertEqual(200, $status, json_encode($body));
        assertEqual(50, $body['import']['received']); assertEqual(50, $body['import']['imported']);
        assertEqual(50, $body['pagination']['total']); assertEqual(10, count($body['docs']));
        assertTrue($body['import']['possibly_truncated']); assertTrue(isset($body['warnings']));
        [$status, $body] = qsearchCall($base . '?q=software&page=2&rows=10');
        assertEqual(200, $status); assertEqual(0, $body['import']['imported']);
        assertEqual(50, $body['import']['existing']); assertEqual(10, count($body['docs']));
    });
    test('request log confirms real HTTP requests reached PHP CUIscan mock', function () use ($temp) {
        $log = file_get_contents($temp . '/cuiscan-requests.ndjson');
        assertTrue(str_contains($log, 'request')); assertTrue(str_contains($log, 'zira'));
    });
} catch (Throwable $e) {
    test('local test infrastructure', function () use ($e) { throw $e; });
} finally {
    foreach (array_reverse($processes) as $proc) { proc_terminate($proc); proc_close($proc); }
    // Logs and fixtures are retained for failure diagnosis; never touches project api.env.
    fwrite(STDERR, "Isolated qsearch logs: $temp\n");
}
finish();
