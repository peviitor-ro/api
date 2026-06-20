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

test("escapes double quote", function() {
    assertEqual('\\"', solrEscape('"'));
});

test("escapes backslash", function() {
    assertEqual('\\\\', solrEscape('\\'));
});

test("escapes all special characters", function() {
    $input  = '+-&&||!(){}[]^"~*?:\\/';
    $output = solrEscape($input);
    assertTrue(str_contains($output, '\\+'));
    assertTrue(str_contains($output, '\\-'));
    assertTrue(str_contains($output, '\\"'));
    assertTrue(str_contains($output, '\\\\'));
    assertTrue(str_contains($output, '\\/'));
});

test("plain text unchanged", function() {
    assertEqual('NUME SRL', solrEscape('NUME SRL'));
    assertEqual('12345678', solrEscape('12345678'));
});

test("empty string", function() {
    assertEqual('', solrEscape(''));
});

test("company with diacritics unchanged", function() {
    assertEqual('SISTEMATIC SRL Bihor', solrEscape('SISTEMATIC SRL Bihor'));
});

test("company with AND and OR logic operators escaped", function() {
    $result = solrEscape('Company && Test || Foo');
    assertTrue(str_contains($result, '\\&\\&'));
    assertTrue(str_contains($result, '\\|\\|'));
});

finish();
