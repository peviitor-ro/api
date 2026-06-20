<?php
require_once __DIR__ . '/../helpers.php';
registerTestFile(__FILE__);

function validateIdentifier(?string $company, ?string $cif, ?string $brand): ?string {
    if (!$company && !$cif && !$brand) {
        return 'At least one of company, cif, or brand is required';
    }
    return null;
}

function validateConfirmation(?string $confirmation): ?string {
    if ($confirmation !== 'CLEAN_COMPANY_JOBS') {
        return 'Confirmation must be "CLEAN_COMPANY_JOBS"';
    }
    return null;
}

function validateLength(?string $value, int $max, string $label): ?string {
    if ($value !== null && strlen($value) > $max) {
        return "$label too long (max $max characters)";
    }
    return null;
}

function validateBody(array $body): array {
    $errors = [];

    $company = isset($body['company']) ? trim($body['company']) : null;
    $cif     = isset($body['cif']) ? trim($body['cif']) : null;
    $brand   = isset($body['brand']) ? trim($body['brand']) : null;

    $err = validateIdentifier($company, $cif, $brand);
    if ($err) $errors[] = $err;

    $err = validateConfirmation($body['confirmation'] ?? null);
    if ($err) $errors[] = $err;

    $err = validateLength($company, 200, 'Company name');
    if ($err) $errors[] = $err;

    $err = validateLength($cif, 20, 'CIF');
    if ($err) $errors[] = $err;

    $err = validateLength($brand, 200, 'Brand name');
    if ($err) $errors[] = $err;

    return $errors;
}

test("rejects empty body", function() {
    $errors = validateBody([]);
    assertTrue(count($errors) > 0);
    assertTrue(str_contains($errors[0], 'at least one'));
});

test("accepts company only", function() {
    $errors = validateBody([
        'company' => 'NUME SRL',
        'confirmation' => 'CLEAN_COMPANY_JOBS'
    ]);
    assertEqual(0, count($errors));
});

test("accepts cif only", function() {
    $errors = validateBody([
        'cif' => '12345678',
        'confirmation' => 'CLEAN_COMPANY_JOBS'
    ]);
    assertEqual(0, count($errors));
});

test("accepts brand only", function() {
    $errors = validateBody([
        'brand' => 'ORANGE',
        'confirmation' => 'CLEAN_COMPANY_JOBS'
    ]);
    assertEqual(0, count($errors));
});

test("rejects wrong confirmation", function() {
    $errors = validateBody([
        'company' => 'NUME SRL',
        'confirmation' => 'WRONG'
    ]);
    assertEqual(1, count($errors));
    assertTrue(str_contains($errors[0], 'Confirmation'));
});

test("rejects missing confirmation", function() {
    $errors = validateBody([
        'company' => 'NUME SRL'
    ]);
    assertTrue(count($errors) > 0);
});

test("rejects company name too long", function() {
    $errors = validateBody([
        'company' => str_repeat('A', 201),
        'confirmation' => 'CLEAN_COMPANY_JOBS'
    ]);
    assertTrue(count($errors) > 0);
    assertTrue(str_contains($errors[0], 'too long'));
});

test("accepts company at max length", function() {
    $errors = validateBody([
        'company' => str_repeat('A', 200),
        'confirmation' => 'CLEAN_COMPANY_JOBS'
    ]);
    assertEqual(0, count($errors));
});

test("rejects cif too long", function() {
    $errors = validateBody([
        'cif' => str_repeat('1', 21),
        'confirmation' => 'CLEAN_COMPANY_JOBS'
    ]);
    assertTrue(count($errors) > 0);
});

test("rejects brand too long", function() {
    $errors = validateBody([
        'brand' => str_repeat('B', 201),
        'confirmation' => 'CLEAN_COMPANY_JOBS'
    ]);
    assertTrue(count($errors) > 0);
});

finish();
