<?php
namespace Peviitor\CompanySearch;

class SearchFailure extends \RuntimeException
{
    public ?array $import = null;
}

class HttpFailure extends \RuntimeException {}

/** Bounded transport. Never follow redirects or send Solr credentials to CUIscan. */
class JsonHttp
{
    public function request(string $method, string $url, ?array $body = null, string $user = '', string $pass = '', bool $expectList = false): array
    {
        $headers = ['Accept: application/json', 'Connection: close'];
        if ($user !== '') { $headers[] = 'Authorization: Basic ' . base64_encode("$user:$pass"); }
        if ($body !== null) { $headers[] = 'Content-Type: application/json'; }
        $context = stream_context_create(['http' => [
            'method' => $method, 'header' => implode("\r\n", $headers),
            'content' => $body === null ? '' : json_encode($body, JSON_THROW_ON_ERROR),
            'timeout' => 8, 'ignore_errors' => true, 'follow_location' => 0,
        ]]);
        $started = microtime(true);
        $stream = @fopen($url, 'rb', false, $context);
        if ($stream === false) {
            throw new HttpFailure('External connection failed', microtime(true) - $started >= 7.5 ? 504 : 502);
        }
        try {
            $data = @stream_get_contents($stream, 2097153);
            $meta = stream_get_meta_data($stream);
        } finally { fclose($stream); }
        if ($meta['timed_out'] ?? false) { throw new HttpFailure('External request timed out', 504); }
        $status = 0;
        foreach ($meta['wrapper_data'] ?? [] as $line) {
            if (preg_match('#^HTTP/\S+ (\d{3})#', $line, $match)) { $status = (int)$match[1]; }
        }
        if ($status < 200 || $status >= 300) {
            throw new HttpFailure('External service returned an error', $status ?: 502);
        }
        if ($data === false || strlen($data) > 2097152) { throw new HttpFailure('External response exceeded the size limit', 502); }
        if ($expectList && substr(ltrim($data), 0, 1) !== '[') {
            throw new HttpFailure('External service did not return a JSON list', 502);
        }
        try { $json = json_decode($data, true, 64, JSON_THROW_ON_ERROR); }
        catch (\JsonException $e) { throw new HttpFailure('External service returned invalid JSON', 502); }
        if (!is_array($json)) { throw new HttpFailure('External service returned an invalid response', 502); }
        return $json;
    }
}

function validateInput(array $params): array
{
    $query = $params['q'] ?? null;
    if (!is_string($query) || preg_match('//u', $query) !== 1 || preg_match('/[\x00-\x1f\x7f]/', $query)) {
        throw new SearchFailure('q must be a UTF-8 search string', 400);
    }
    $query = preg_replace('/^\s+|\s+$/u', '', $query);
    $length = preg_match_all('/./u', $query);
    if ($length < 2 || $length > 100) { throw new SearchFailure('q must contain between 2 and 100 characters', 400); }
    $numbers = [];
    foreach (['page' => 1, 'rows' => 10] as $key => $default) {
        $value = $params[$key] ?? (string)$default;
        if (!is_scalar($value) || !preg_match('/^\d{1,7}$/D', (string)$value)) {
            throw new SearchFailure("$key must be a non-negative integer", 400);
        }
        $numbers[$key] = max(1, (int)$value);
    }
    $numbers['rows'] = min(100, $numbers['rows']);
    return ['q' => $query] + $numbers;
}

/** Shared across this checkout's workers: 100 calls/rolling hour, 500ms spacing. */
function reserveCuiscanRequest(): void
{
    $path = sys_get_temp_dir() . '/peviitor-cuiscan-' . hash('sha256', __DIR__) . '.json';
    $fp = @fopen($path, 'c+');
    if (!$fp) { throw new SearchFailure('CUIscan rate limiter unavailable', 503); }
    try {
        if (!flock($fp, LOCK_EX | LOCK_NB)) { throw new SearchFailure('CUIscan request in progress; retry later', 503); }
        $times = json_decode(stream_get_contents($fp), true);
        $times = is_array($times) ? $times : [];
        $now = microtime(true);
        $times = array_values(array_filter($times, fn($time) => is_numeric($time) && $time > $now - 3600));
        if (count($times) >= 100) { throw new SearchFailure('CUIscan hourly request budget exhausted; retry later', 503); }
        $last = $times ? max($times) : 0;
        $delay = max(0, 0.5 - ($now - $last));
        if ($delay > 0) { usleep((int)ceil($delay * 1000000)); }
        $times[] = microtime(true);
        rewind($fp);
        if (!ftruncate($fp, 0) || fwrite($fp, json_encode($times)) === false || !fflush($fp)) {
            throw new SearchFailure('CUIscan rate limiter unavailable', 503);
        }
    } finally { flock($fp, LOCK_UN); fclose($fp); }
}

class CuiscanClient
{
    private JsonHttp $http;
    private string $url;
    public function __construct(JsonHttp $http, string $url = 'https://cuiscan.ro/api.php')
    {
        $this->http = $http;
        $this->url = $url;
    }

    public function search(string $query): array
    {
        reserveCuiscanRequest();
        try {
            $rows = $this->http->request('GET', $this->url . '?' . http_build_query(
                ['action' => 'search', 'q' => $query], '', '&', PHP_QUERY_RFC3986
            ), null, '', '', true);
        } catch (HttpFailure $e) {
            $status = $e->getCode() === 504 ? 504 : ($e->getCode() === 429 ? 503 : 502);
            throw new SearchFailure($status === 503 ? 'CUIscan rate limit reached; retry later' :
                ($status === 504 ? 'CUIscan request timed out' : 'CUIscan search unavailable'), $status);
        }
        // The documentation says 30, but live searches can return 50 or more.
        // Validate the shape, not the documented count; the HTTP size bound still applies.
        if (!array_is_list($rows)) { throw new SearchFailure('CUIscan returned an unsupported search response', 502); }
        // Validate every company before the first database write.
        $companies = [];
        foreach ($rows as $row) {
            if (!is_array($row) || !(is_int($row['cui'] ?? null) || is_string($row['cui'] ?? null)) ||
                !is_string($row['name'] ?? null) || trim($row['name']) === '') {
                throw new SearchFailure('CUIscan returned invalid company data', 502);
            }
            $id = ltrim(preg_replace('/^RO\s*/i', '', trim((string)$row['cui'])), '0');
            if (!preg_match('/^\d{2,10}$/D', $id)) { throw new SearchFailure('CUIscan returned an invalid CUI', 502); }
            $location = [];
            foreach (['locality', 'county'] as $field) {
                if (isset($row[$field]) && !is_string($row[$field])) { throw new SearchFailure('CUIscan returned invalid location data', 502); }
                if (trim($row[$field] ?? '') !== '') { $location[] = trim($row[$field]); }
            }
            $doc = ['id' => $id, 'company' => trim($row['name'])];
            if ($location) { $doc['location'] = [implode(', ', array_unique($location))]; }
            // The provider also accepts CUI searches; this endpoint imports name matches only.
            if (preg_match('/' . preg_quote($query, '/') . '/iu', $doc['company']) !== 1) { continue; }
            $companies[$id] = $doc;
        }
        return ['companies' => array_values($companies), 'received' => count($rows), 'possibly_truncated' => count($rows) >= 30];
    }
}

class CompanyRepository
{
    private JsonHttp $http;
    private string $base;
    private string $user;
    private string $pass;
    public function __construct(JsonHttp $http, string $base, string $user = '', string $pass = '')
    {
        $this->http = $http; $this->base = $base; $this->user = $user; $this->pass = $pass;
    }
    private function request(string $method, string $path, ?array $body = null): array
    {
        return $this->http->request($method, $this->base . $path, $body, $this->user, $this->pass);
    }
    public function createIfAbsent(array $company): bool
    {
        try {
            // Real-time get sees writes even before they are searchable.
            $found = $this->request('GET', '/get?wt=json&id=' . rawurlencode($company['id']));
            if (!array_key_exists('doc', $found)) { throw new HttpFailure('Invalid Solr get response', 502); }
            if ($found['doc'] !== null) { return false; }
            // Optimistic concurrency: -1 requires that the ID does not exist.
            $result = $this->request('POST', '/update?wt=json&commit=true', [$company + ['_version_' => -1]]);
            if (($result['responseHeader']['status'] ?? null) !== 0) { throw new HttpFailure('Invalid Solr update response', 502); }
            return true;
        } catch (HttpFailure $e) {
            if ($e->getCode() === 409) { return false; }
            throw new SearchFailure('Company storage unavailable; earlier imports may have succeeded', 503);
        }
    }
    public function search(array $input): array
    {
        // Literal substring: user Solr operators cannot become query syntax.
        $literal = preg_replace('/([+\-&|!(){}\[\]^"~*?:\\\\\/\s])/', '\\\\$1', $input['q']);
        $query = 'company:*' . $literal . '*';
        if (preg_match('/\s/u', $input['q'])) {
            // A wildcard term cannot span the tokens produced by text analysis.
            // Complexphrase keeps terms adjacent and allows partial boundary words.
            // Its quoted content is parsed twice, so escape each parser layer.
            $inner = preg_replace('/([+\-&|!(){}\[\]^"~*?:\\\\\/])/', '\\\\$1', $input['q']);
            $inner = preg_replace('/\b(AND|OR|NOT)\b/', '\\\\$1', $inner);
            $outer = str_replace(['\\', '"'], ['\\\\', '\\"'], $inner);
            $query = '{!complexphrase inOrder=true}company:"*' . $outer . '*"';
        }
        $params = ['q' => $query, 'wt' => 'json',
            'start' => ($input['page'] - 1) * $input['rows'], 'rows' => $input['rows']];
        try { $result = $this->request('GET', '/select?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986)); }
        catch (HttpFailure $e) { throw new SearchFailure('Failed to retrieve companies from Solr', 503); }
        if (!isset($result['response']['docs'], $result['response']['numFound']) ||
            !is_array($result['response']['docs']) || !is_int($result['response']['numFound'])) {
            throw new SearchFailure('Invalid company search response from Solr', 503);
        }
        $docs = array_map(function ($doc) { unset($doc['_version_'], $doc['_root_']); return $doc; }, $result['response']['docs']);
        $total = $result['response']['numFound'];
        return ['pagination' => ['page' => $input['page'], 'rows' => $input['rows'], 'total' => $total,
            'total_pages' => ceil($total / $input['rows'])], 'docs' => $docs];
    }
}

function searchAndImport(array $input, CuiscanClient $client, CompanyRepository $repository): array
{
    $source = $client->search($input['q']);
    $import = ['source' => 'cuiscan.ro', 'received' => $source['received'], 'matched' => count($source['companies']),
        // source_limit retains the provider's documented threshold for compatibility;
        // it is not a cap enforced by this importer.
        'imported' => 0, 'existing' => 0, 'source_limit' => 30, 'source_pagination_supported' => false,
        'possibly_truncated' => $source['possibly_truncated']];
    try {
        foreach ($source['companies'] as $company) {
            $key = $repository->createIfAbsent($company) ? 'imported' : 'existing';
            $import[$key]++;
        }
        $result = $repository->search($input);
    } catch (SearchFailure $e) { $e->import = $import; throw $e; }
    $result['import'] = $import;
    if ($source['possibly_truncated']) {
        $result['warnings'] = ['CUIscan returned at least its documented result limit and provides no documented pagination. All returned matches were processed, but more matches may exist.'];
    }
    return $result;
}
