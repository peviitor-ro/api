<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../../v1/firme/qsearch/lib/search.php';
registerTestFile(__FILE__);

use Peviitor\CompanySearch\JsonHttp;
use Peviitor\CompanySearch\CompanyRepository;
use Peviitor\CompanySearch\HttpFailure;
use function Peviitor\CompanySearch\validateInput;

class QsearchRecordingHttp extends JsonHttp {
    public array $calls = [];
    public array $answers = [];
    public function request(string $method, string $url, ?array $body = null, string $user = '', string $pass = '', bool $expectList = false): array {
        $this->calls[] = compact('method', 'url', 'body', 'user', 'pass');
        $answer = array_shift($this->answers);
        if ($answer instanceof Throwable) { throw $answer; }
        return $answer;
    }
}

test('validates Unicode length, trims whitespace and does not double-decode input', function () {
    assertEqual('zi%26ra', validateInput(['q' => ' zi%26ra '])['q']);
    assertEqual('ȘȚ', validateInput(['q' => 'ȘȚ'])['q']);
    foreach ([null, [], ' ', 'a', str_repeat('a', 101), "\xffbad", "zi\nra"] as $q) {
        assertThrows(fn() => validateInput(['q' => $q]));
    }
    assertEqual(100, validateInput(['q' => 'zira', 'rows' => '999'])['rows']);
});

test('Solr metacharacters remain a literal company substring', function () {
    $http = new QsearchRecordingHttp();
    $http->answers = [['response' => ['numFound' => 0, 'docs' => []]]];
    $repo = new CompanyRepository($http, 'http://example.test/solr/company');
    $repo->search(validateInput(['q' => 'a"*:*', 'page' => '2', 'rows' => '3']));
    parse_str(parse_url($http->calls[0]['url'], PHP_URL_QUERY), $params);
    assertEqual('company:*a\\"\\*\\:\\**', $params['q']);
    assertEqual('3', $params['start']);
});

test('multiword company names use an ordered wildcard phrase', function () {
    $http = new QsearchRecordingHttp();
    $http->answers = [['response' => ['numFound' => 0, 'docs' => []]]];
    $repo = new CompanyRepository($http, 'http://example.test/solr/company');
    $repo->search(validateInput(['q' => 'pepper events']));
    parse_str(parse_url($http->calls[0]['url'], PHP_URL_QUERY), $params);
    assertEqual('{!complexphrase inOrder=true}company:"*pepper events*"', $params['q']);
});

test('company writes require absence and commit before search', function () {
    $http = new QsearchRecordingHttp();
    $http->answers = [['doc' => null], ['responseHeader' => ['status' => 0]]];
    $repo = new CompanyRepository($http, 'http://example.test/solr/company', 'user', 'pass');
    assertTrue($repo->createIfAbsent(['id' => '88000001', 'company' => 'ZIRA TEST']));
    assertEqual(-1, $http->calls[1]['body'][0]['_version_']);
    assertTrue(str_contains($http->calls[1]['url'], 'commit=true'));
    assertEqual('user', $http->calls[0]['user']);
});

test('malformed Solr acknowledgement is an error', function () {
    $http = new QsearchRecordingHttp();
    $http->answers = [['doc' => null], ['unexpected' => true]];
    $repo = new CompanyRepository($http, 'http://example.test/solr/company');
    assertThrows(fn() => $repo->createIfAbsent(['id' => '88000001', 'company' => 'ZIRA TEST']));
});
finish();
