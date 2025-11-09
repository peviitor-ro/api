<?php
// Get the search query from the URL parameter 'q' first

$q = $_GET['q'];
// Add trailing slash if missing
if (substr($q, -1) !== '/') {
    $q .= '/';
}

// Define log file path
$logFile = __DIR__ . '/../../'.$q . 'error_log';
//echo $logFile;

if (is_file($logFile)) {
    $content = file_get_contents($logFile);


        echo '<pre>' . htmlspecialchars($content) . '</pre>';
    }

?>
