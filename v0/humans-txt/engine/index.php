<?php
$rawDomain = isset($_POST['rawDomain']) ? $_POST['rawDomain'] : "https:\/\/peviitor.ro";
$rawDomain = isset($_GET['domain']) ? $_GET['domain'] : $rawDomain;
function addProtocolToDomain($domain) {
    if (strpos($domain, 'http://') !== 0 && strpos($domain, 'https://') !== 0) {
        $domain = 'https://' . $domain; // Add "https://" as the protocol
    }
    return $domain;
}

function addWwwToDomain($domain) {
    if (strpos($domain, 'www.') !== 0) {
        $domain = 'www.' . $domain; // Add "www" if it's not already there
    }
    return $domain;
}

function checkHumansTxtExistence($domain) {
    $humansTxtURL = $domain . '/humans.txt';
    $headers = @get_headers($humansTxtURL);
    
    return strpos($headers[0], '200 OK') !== false;
}

// Remove backslashes and call the function to add the protocol
$domainWithProtocol = addProtocolToDomain(stripslashes($rawDomain));

// Call the function to add "www" to the domain if necessary
$domainWithWww = addWwwToDomain($domainWithProtocol);

// Call the function to check the existence of humans.txt
$humansTxtExists = checkHumansTxtExistence($domainWithWww);

$response = [
    'domain' => $domainWithWww,
    'humans.txt' => $humansTxtExists,
];

header('Content-Type: application/json');
echo json_encode($response);
?>
