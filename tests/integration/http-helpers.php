<?php
function apiCall(string $method, string $path, array $headers = [], ?string $body = null, int $port = 8080): array {
    $h = [];
    foreach ($headers as $k => $v) {
        $h[] = "$k: $v";
    }
    $opts = [
        'http' => [
            'method'  => $method,
            'header'  => implode("\r\n", $h),
            'content' => $body,
            'timeout' => 5,
            'ignore_errors' => true
        ]
    ];
    $url = "http://127.0.0.1:$port$path";
    $context = stream_context_create($opts);
    $response = @file_get_contents($url, false, $context);

    $httpCode = 0;
    if (isset($http_response_header)) {
        preg_match('#HTTP/[0-9.]+ (\d+)#', $http_response_header[0], $m);
        $httpCode = (int)$m[1];
    }

    return [
        'code' => $httpCode,
        'body' => $response !== false ? json_decode($response, true) : null,
        'raw'  => $response
    ];
}
