<?php
$rawDomain = isset($_POST['domain']) ? $_POST['domain'] : "https:\/\/peviitor.ro";
$rawDomain = isset($_GET['domain']) ? $_GET['domain'] : $rawDomain;
function addProtocolToDomain($domain) {
    if (strpos($domain, 'http://') !== 0 && strpos($domain, 'https://') !== 0) {
        $domain = 'https://' . $domain; // Add "https://" as the protocol
    }
    return $domain;
}

function checkHumansTxtExistence($domain) {
    // Initialize cURL session
    $ch = curl_init();
    
    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $domain . '/humans.txt');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Follow redirects
    curl_setopt($ch, CURLOPT_NOBODY, true); // Only check the header, not the content

    // Execute cURL and get the response
    $headers = curl_exec($ch);
    
    // Get the HTTP response code
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // Close the cURL session
    curl_close($ch);
    
    // Check if the response code is 200 OK
    return $httpCode === 200;
}

// Remove backslashes and call the function to add the protocol
$domainWithProtocol = addProtocolToDomain(stripslashes($rawDomain));

// Call the function to check the existence of humans.txt
$humansTxtExists = checkHumansTxtExistence($domainWithProtocol);

$response = [
    'domain' => $domainWithProtocol,
    'humans.txt' => $humansTxtExists,
];

header('Content-Type: application/json');
echo json_encode($response, JSON_UNESCAPED_SLASHES);
?>
