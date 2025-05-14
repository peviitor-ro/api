<?php
// Allow cross-origin requests
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');

// Ensure the request is GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["error" => "Only GET method is allowed"]);
    exit;
}

// Load variables from the .env file
function loadEnv($file)
{
    $file = realpath($file);

    // Check if the .env file exists
    if (!$file || !file_exists($file)) {
        die(json_encode(["error" => "The .env file does not exist!", "path" => $file]));
    }

    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments (lines starting with '#')
        if (strpos(trim($line), '#') === 0) continue;

        // Split the line into key and value, and add to environment
        list($key, $value) = explode('=', $line, 2) + [NULL, NULL];
        if ($key && $value) {
            $key = trim($key);
            $value = trim($value);
            $_SERVER[$key] = $value;
            putenv("$key=$value");
        }
    }
}

// Load .env file
loadEnv('../../api.env');

// Retrieve SOLR variables from environment
$server = getenv('PROD_SERVER') ?: ($_SERVER['PROD_SERVER'] ?? null);
$backup = getenv('BACK_SERVER') ?: ($_SERVER['BACK_SERVER'] ?? null);
$username = getenv('SOLR_USER') ?: ($_SERVER['SOLR_USER'] ?? null);
$password = getenv('SOLR_PASS') ?: ($_SERVER['SOLR_PASS'] ?? null);

// Debugging: Check if the server is set
if (!$server) {
    die(json_encode(["error" => "PROD_SERVER is not set in .env"]));
}

// Fetch the data from Solr
$context = stream_context_create([
    'http' => [
        'header' => "Authorization: Basic " . base64_encode("$username:$password")
    ]
]);

// Define the core for Solr
$core = "jobs";

// Function to fetch companies from Solr based on user input
function getCompanies($userInput)
{
    global $server, $core, $username, $password, $context;

    // Create the query string parameter, properly encoded
    $qs = [
        'fl' => 'company_str',
        'indent' => 'true',
        'q.op' => 'OR',
        'q' => 'company:*' . $userInput . '*',
        'sort' => 'company_str asc',
        'useParams' => '',
        'group' => 'true',
        'group.field' => 'company_str',
        'group.limit' => '1'
    ];

    // Construct the URL for the Solr request
    $url = 'http://' . $server . '/solr/' . $core . '/select?' . $qs;

    // Fetch data from Solr
    $string = file_get_contents($url, false, $context);

    // Check if Solr is down (server not responding)
    if ($string == false) {
        http_response_code(503);
        echo json_encode([
            "error" => "SOLR server in DEV is down",
            "code" => 503
        ]);
        exit;
    }

    $json = json_decode($string, true);

    if ($json === null) {
        return json_encode(array("message" => "Invalid JSON response from Solr."));
    }

    // Extract the companies from the response
    if (!isset($json['grouped']['company_str']['groups'])) {
        return json_encode(array("message" => "No company data found in Solr response."));
    }

    $groups = $json['grouped']['company_str']['groups'];
    $results = array();

    // Iterate through the groups and add them to the results
    foreach ($groups as $group) {
        if (isset($group['groupValue'])) {
            $results[] = $group['groupValue'];
        }
    }

    // Check if no matching companies were found
    if (empty($results)) {
        return json_encode(array("message" => "Nu au fost găsite companii cu acest nume"));
    }

    // Return the results as a JSON-encoded array
    return json_encode($results);
}

// Function to fetch the first 25 companies from Solr
function getFirst25Companies()
{
    global $server, $core, $username, $password, $context;

    // Construct the query string to fetch the first 25 companies
    $qs = [
        'facet.field' => 'company_str',
        'facet' => 'true',
        'facet.limit' => '25',
        'fl' => 'company',
        'indent' => 'true',
        'q.op' => 'OR',
        'useParams' => '',
        'q' => '*:*'
    ];

    // Construct the URL for the Solr request
    $url = 'http://' . $server . '/solr/' . $core . '/select?' . http_build_query($qs);

    // Fetch data from Solr
    $string = file_get_contents($url, false, $context);

    // Check if Solr is down (server not responding)
    if ($string == false) {
        http_response_code(503);
        echo json_encode([
            "error" => "SOLR server in DEV is down",
            "code" => 503
        ]);
        exit;
    }

    $json = json_decode($string, true);


    if (!isset($json['facet_counts']['facet_fields']['company_str'])) {
        return json_encode(array("message" => "No company data found in Solr response."));
    }

    $companies = $json['facet_counts']['facet_fields']['company_str'];
    $results = array();

    // Iterate through the companies and add them to the results
    for ($i = 0; $i < count($companies) / 2; $i++) {
        $k = 2 * $i;
        $companyName = $companies[$k];
        $results[] = $companyName;
    }

    // Return the results as a JSON-encoded array
    return json_encode($results);
}

try {

    // Verificăm disponibilitatea endpoint-ului
    $headers = @get_headers(
        'http://' . $server . '/solr/' . $core . '/select?q=*:*&rows=1'
    );
    if ($headers === false || strpos($headers[0], '200') === false) {
        throw new Exception('Endpoint-ul nu este disponibil');
    }
    // Retrieve the user input from the query parameter
    $userInput = isset($_GET['userInput']) ? $_GET['userInput'] : '';

    // Fetch the companies based on the user input or fetch the first 25 companies if no input is provided
    if ($userInput) {
        echo getCompanies($userInput);
    } else {
        echo getFirst25Companies();
    }
} catch (Exception $e) {
    // Fallback at the backup endpoint
    $backupUrl = $backup . '/mobile/companies/';
    $fallbackQuery = isset($_GET['userInput']) ? '?search=' . $_GET['userInput'] : '';
    $json = file_get_contents($backupUrl . $fallbackQuery);
    $companies = json_decode($json, true);
    echo json_encode($companies['results'] ?? []);
}