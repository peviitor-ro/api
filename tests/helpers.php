<?php
$GLOBALS['_tests'] = [];
$GLOBALS['_test_file'] = '';

function test(string $name, callable $fn): void {
    $start = microtime(true);
    try {
        $fn();
        $GLOBALS['_tests'][] = [
            'name'    => $name,
            'pass'    => true,
            'time'    => round((microtime(true) - $start) * 1000, 2)
        ];
    } catch (Throwable $e) {
        $GLOBALS['_tests'][] = [
            'name'    => $name,
            'pass'    => false,
            'time'    => round((microtime(true) - $start) * 1000, 2),
            'error'   => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine()
        ];
    }
}

function assertEqual(mixed $expected, mixed $actual, string $msg = ''): void {
    if ($expected !== $actual) {
        throw new Exception($msg ?: sprintf("Expected %s but got %s",
            json_encode($expected, JSON_UNESCAPED_UNICODE),
            json_encode($actual, JSON_UNESCAPED_UNICODE)));
    }
}

function assertTrue(bool $condition, string $msg = ''): void {
    if (!$condition) {
        throw new Exception($msg ?: "Expected true but got false");
    }
}

function assertThrows(callable $fn, string $msg = ''): void {
    $threw = false;
    try {
        $fn();
    } catch (Throwable) {
        $threw = true;
    }
    if (!$threw) {
        throw new Exception($msg ?: "Expected exception but none thrown");
    }
}

function assertNotEqual(mixed $expected, mixed $actual, string $msg = ''): void {
    if ($expected === $actual) {
        throw new Exception($msg ?: sprintf("Expected not %s", json_encode($expected, JSON_UNESCAPED_UNICODE)));
    }
}

function registerTestFile(string $file): void {
    $GLOBALS['_test_file'] = basename($file, '.php');
}

function finish(): never {
    $result = [
        'type'   => 'suite',
        'file'   => $GLOBALS['_test_file'],
        'tests'  => $GLOBALS['_tests'],
        'total'  => count($GLOBALS['_tests']),
        'passed' => count(array_filter($GLOBALS['_tests'], fn($t) => $t['pass'])),
        'failed' => count(array_filter($GLOBALS['_tests'], fn($t) => !$t['pass'])),
    ];
    echo json_encode($result, JSON_UNESCAPED_UNICODE) . "\n";
    exit($result['failed'] > 0 ? 1 : 0);
}

function beforeAll(callable $fn): void { $fn(); }

function afterAll(callable $fn): void { $fn(); }
