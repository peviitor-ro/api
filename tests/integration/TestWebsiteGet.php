<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/http-helpers.php';
registerTestFile(__FILE__);

// Run the actual endpoint in an isolated tree; never overwrite the developer's api.env.
$root = dirname(__DIR__, 2);
$temp = sys_get_temp_dir() . '/peviitor-website-' . bin2hex(random_bytes(6));
mkdir($temp . '/v1/firme/website/get', 0777, true);
mkdir($temp . '/util');
copy($root . '/v1/firme/website/get/index.php', $temp . '/v1/firme/website/get/index.php');
foreach (['loadEnv.php', 'http.php'] as $file) {
    copy($root . '/util/' . $file, $temp . '/util/' . $file);
}
$stateFile = $temp . '/state.json';
$processes = [];
register_shutdown_function(function () use (&$processes, $temp) {
    foreach ($processes as $process) {
        proc_terminate($process);
        proc_close($process);
    }
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($temp, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($files as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }
    rmdir($temp);
});

function websiteFreePort(): int {
    $socket = stream_socket_server('tcp://127.0.0.1:0');
    $address = stream_socket_get_name($socket, false);
    fclose($socket);
    return (int)substr(strrchr($address, ':'), 1);
}

function websiteServer(array $args, string $log, array $env): void {
    global $processes;
    $process = proc_open(array_merge([PHP_BINARY], $args), [
        0 => ['pipe', 'r'], 1 => ['file', $log, 'a'], 2 => ['file', $log, 'a']
    ], $pipes, null, $env);
    if (!is_resource($process)) {
        throw new Exception('Could not start PHP test server');
    }
    fclose($pipes[0]);
    $processes[] = $process;
    $address = $args[1];
    for ($i = 0; $i < 100; $i++) {
        $socket = @stream_socket_client('tcp://' . $address, $errno, $error, 0.1);
        if ($socket) {
            fclose($socket);
            return;
        }
        usleep(20000);
    }
    throw new Exception('PHP test server did not start: ' . file_get_contents($log));
}

$mockPort = websiteFreePort();
$apiPort = websiteFreePort();
$env = array_merge(getenv(), ['WEBSITE_TEST_STATE' => $stateFile]);
file_put_contents($stateFile, json_encode(['scenario' => 'success', 'calls' => 0]));
$config = "SOLR_SERVER=127.0.0.1:$mockPort\nPROTOCOL=http\nSOLR_USER=test-user\nSOLR_PASS=test-pass\n";
file_put_contents($temp . '/api.env', $config);
websiteServer(['-S', "127.0.0.1:$mockPort", __DIR__ . '/website-solr-handler.php'], $temp . '/solr.log', $env);
websiteServer(['-S', "127.0.0.1:$apiPort", '-t', $temp], $temp . '/api.log', $env);
$request = fn($method = 'GET') => apiCall($method, '/v1/firme/website/get/', [], null, $apiPort);

test('returns all 1005 valid companies across capped pages, skipping unusable websites', function () use ($request, $stateFile) {
    $res = $request();
    assertEqual(200, $res['code'], $res['raw']);
    assertEqual(1005, count($res['body']));
    assertEqual('00500', $res['body'][0]['id']);
    assertEqual('01504', $res['body'][1004]['id']);
    assertEqual(1005, count(array_unique(array_column($res['body'], 'id'))));
    assertEqual(5, json_decode(file_get_contents($stateFile), true)['calls']);
});

test('preserves complete documents, scalar and array websites, and multiple valid values', function () use ($request) {
    $docs = $request()['body'];
    assertEqual('https://example.com', $docs[0]['website']);
    assertEqual(['', 'invalid', 'https://example.com', 'https://example.org'], $docs[1]['website']);
    assertEqual(['Example SRL'], $docs[0]['company']);
    assertEqual(['Bucharest'], $docs[0]['location']);
    assertEqual(['Example'], $docs[0]['brand']);
    assertEqual(['nested' => ['kept' => true]], $docs[0]['custom_details']);
    assertEqual(42, $docs[0]['_version_']);
});

foreach (['POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'] as $method) {
    test("$method returns 405 without querying Solr", function () use ($request, $stateFile, $method) {
        file_put_contents($stateFile, json_encode(['scenario' => 'success', 'calls' => 0]));
        $res = $request($method);
        assertEqual(405, $res['code']);
        assertEqual('Only GET method allowed', $res['body']['error']);
        assertEqual(0, json_decode(file_get_contents($stateFile), true)['calls']);
    });
}

foreach (['empty', 'invalid-only'] as $scenario) {
    test("$scenario returns an empty JSON array", function () use ($stateFile, $request, $scenario) {
        file_put_contents($stateFile, json_encode(['scenario' => $scenario, 'calls' => 0]));
        $res = $request();
        assertEqual(200, $res['code']);
        assertEqual('[]', $res['raw']);
    });
}

foreach (['http-error', 'invalid-json', 'malformed', 'partial', 'missing-cursor', 'solr-error', 'later-error'] as $scenario) {
    test("$scenario returns JSON 503 without partial company data", function () use ($stateFile, $request, $scenario) {
        file_put_contents($stateFile, json_encode(['scenario' => $scenario, 'calls' => 0]));
        $res = $request();
        assertEqual(503, $res['code'], $res['raw']);
        assertEqual(['error' => 'Company core unavailable'], $res['body']);
    });
}

test('unreachable Solr returns 503', function () use ($temp, $request) {
    $port = websiteFreePort();
    file_put_contents($temp . '/api.env', "SOLR_SERVER=127.0.0.1:$port\nPROTOCOL=http\n");
    assertEqual(503, $request()['code']);
});

test('missing configuration returns 503; unsupported methods still return 405', function () use ($temp, $request) {
    unlink($temp . '/api.env');
    assertEqual(503, $request()['code']);
    assertEqual(405, $request('POST')['code']);
});

test('OpenAPI contract documents the new route and response types', function () use ($root) {
    $spec = json_decode(file_get_contents($root . '/v1/swagger.json'), true, 512, JSON_THROW_ON_ERROR);
    $operation = $spec['paths']['/firme/website/get/']['get'];
    assertEqual(["Companies' websites"], $operation['tags']);
    assertEqual('array', $operation['responses']['200']['content']['application/json']['schema']['type']);
    assertTrue(isset($operation['responses']['405'], $operation['responses']['503']));
});

finish();
