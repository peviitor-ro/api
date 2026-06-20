<?php
require_once __DIR__ . '/../helpers.php';
registerTestFile(__FILE__);

function solrEscape(string $value): string {
    $special = ['+', '-', '&', '|', '!', '(', ')', '{', '}', '[', ']', '^', '"', '~', '*', '?', ':', '\\', '/'];
    $result = '';
    for ($i = 0; $i < strlen($value); $i++) {
        $char = $value[$i];
        if (in_array($char, $special, true)) {
            $result .= '\\' . $char;
        } else {
            $result .= $char;
        }
    }
    return $result;
}

function buildDeleteQuery(?string $company, ?string $cif, array $brandCifs = []): string {
    $parts = [];
    if ($cif) {
        $parts[] = 'cif:' . solrEscape($cif);
    }
    if ($company) {
        $parts[] = 'company:"' . solrEscape($company) . '"';
    }
    if (!empty($brandCifs)) {
        $cifList = implode(' OR ', array_map(fn($c) => 'cif:' . solrEscape($c), $brandCifs));
        if (!empty($parts)) {
            $parts[] = '(' . $cifList . ')';
        } else {
            $parts[] = $cifList;
        }
    }
    return implode(' AND ', $parts);
}

test("query with company only", function() {
    $q = buildDeleteQuery('NUME SRL', null);
    assertEqual('company:"NUME SRL"', $q);
});

test("query with cif only", function() {
    $q = buildDeleteQuery(null, '12345678');
    assertEqual('cif:12345678', $q);
});

test("query with company and cif", function() {
    $q = buildDeleteQuery('NUME SRL', '12345678');
    assertEqual('cif:12345678 AND company:"NUME SRL"', $q);
});

test("query with brand from lookup", function() {
    $q = buildDeleteQuery(null, null, ['111', '222']);
    assertEqual('cif:111 OR cif:222', $q);
});

test("query with company and brand", function() {
    $q = buildDeleteQuery('NUME SRL', null, ['111', '222']);
    assertEqual('company:"NUME SRL" AND (cif:111 OR cif:222)', $q);
});

test("query escapes special chars in company", function() {
    $q = buildDeleteQuery('Test "Company"', null);
    assertEqual('company:"Test \\"Company\\""', $q);
});

test("query with company cif and brand", function() {
    $q = buildDeleteQuery('NUME SRL', '12345', ['111']);
    assertEqual('cif:12345 AND company:"NUME SRL" AND (cif:111)', $q);
});

test("empty query when nothing given", function() {
    $q = buildDeleteQuery(null, null);
    assertEqual('', $q);
});

test("query with cif containing special chars", function() {
    $q = buildDeleteQuery(null, '123+456');
    assertEqual('cif:123\\+456', $q);
});

finish();
