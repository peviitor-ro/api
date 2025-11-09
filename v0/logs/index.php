<?php
// Get the search query from the URL parameter 'q' first
$search = isset($_GET['q']) ? $_GET['q'] : null;

// Define log file path
$logFile = __DIR__ . '/../../'. $search . 'error_log';
//echo $logFile;

if (is_file($logFile)) {
    $content = file_get_contents($logFile);

    if ($search) {
        // Escape special characters for safe regex search
        $searchEscaped = preg_quote($search, '/');

        // Find all lines that contain the search query
        preg_match_all("/^.*$searchEscaped.*$/m", $content, $matches);

        if (!empty($matches[0])) {
            echo '<pre>' . htmlspecialchars(implode("\n", $matches[0])) . '</pre>';
        } else {
            echo "No entries found matching the query.";
        }
    } else {
        // No search query, output entire file content safely
        echo '<pre>' . htmlspecialchars($content) . '</pre>';
    }
} else {
    echo "Fișierul error_log nu există în folderul curent.";
}
?>
