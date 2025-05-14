<?php
/**
 * Load environment variables from a .env file
 *
 * @param string $file Path to the .env file
 * @return void
 */
function loadEnv(string $file = '../../api.env'): void {
    $filePath = realpath($file);

    if (!$filePath || !is_file($filePath)) {
        http_response_code(500);
        die(json_encode([
            "error" => "The .env file does not exist!",
            "path" => $file
        ]));
    }

    foreach (file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);

        // Ignore comments and empty lines
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        // Parse key=value pair
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            [$key, $value] = $parts;
            $key = trim($key);
            $value = trim($value);

            // Optionally handle quoted values (e.g., "VALUE" or 'VALUE')
            $value = trim($value, "\"'");

            $_SERVER[$key] = $value;
            putenv("$key=$value");
        }
    }
}

?>