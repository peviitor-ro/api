<?php
// File path
$filePath = "../server.txt";

// Read the contents of the file into a string
$fileContents = file_get_contents($filePath);

if ($fileContents !== false) {
    echo "File contents:\n";
    echo $fileContents;
} else {
    echo "Unable to read the file.";
}
?>
