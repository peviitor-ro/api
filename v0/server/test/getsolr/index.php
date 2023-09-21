<?php
// File path
$filePath = "../server.txt";

// Read the contents of the file into a string
$fileContents = file_get_contents($filePath);

if ($fileContents !== false) {
       echo $fileContents;
} 
?>
