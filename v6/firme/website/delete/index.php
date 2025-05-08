<?php
// Setează header-urile corespunzătoare pentru CORS
 header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Tratamentul cererilor preflight (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Oprește execuția scriptului după trimiterea headerelor
    http_response_code(200);
    exit;
}



// Load variables from the api.env file
function loadEnv($file)
{
    $file = realpath($file);

    // Check if the api.env file exists
    if (!$file || !file_exists($file)) {
        die(json_encode(["error" => "The api.env file does not exist!", "path" => $file]));
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


class SolrQueryBuilder
{
    public static function replaceSpaces($string)
    {
        return str_replace([' ', '&', '$'], ['%20', '%26', '%24'], $string);
    }

    public static function buildParamQuery($param, $queryName)
    {
        $arrayParams = explode(',', $param);
        $queries = array_map(function ($item) use ($queryName) {
            return $queryName . '%3A%22' . self::replaceSpaces($item) . '%22';
        }, $arrayParams);

        return '&fq=' . implode('%20OR%20', $queries);
    }

    public static function normalizeString($str)
    {
        $charMap = [
            'ă' => 'a',
            'î' => 'i',
            'â' => 'a',
            'ș' => 's',
            'ț' => 't',
            'Ă' => 'A',
            'Î' => 'I',
            'Â' => 'A',
            'Ș' => 'S',
            'Ț' => 'T'
        ];
        return strtr($str, $charMap);
    }
}


    // Load api.env file
loadEnv('../../../../api.env');

// Retrieve SOLR variables from environment
$server = getenv('PROD_SERVER') ?: ($_SERVER['PROD_SERVER'] ?? null);
$username = getenv('SOLR_USER') ?: ($_SERVER['SOLR_USER'] ?? null);
$password = getenv('SOLR_PASS') ?: ($_SERVER['SOLR_PASS'] ?? null);
$back = getenv('BACK_SERVER') ?: ($_SERVER['BACK_SERVER'] ?? null);

// Debugging: Check if the server is set
if (!$server) {
    die(json_encode(["error" => "PROD_SERVER is not set in api.env"]));
}



// Definirea configurației serverului Solr
$server = 'zimbor.go.ro';
$core = 'firme';
$command = '/update';

$url = "http://$server/solr/$core$command?commit=true&wt=json";

// Primirea datelor din cererea DELETE; de obicei nu se trimite corp în cererile DELETE, așa că folosim parametrii din URL
$input = file_get_contents("php://input");
$data = json_decode($input, true);
$id = isset($data['id']) ? htmlspecialchars($data['id']) : null;

// Verifică dacă este furnizat ID-ul
if (empty($id)) {
    http_response_code(400); // Bad Request
    echo json_encode(["error" => "Missing required field 'id'."]);
    exit;
}

// Prepară datele pentru a elimina câmpul 'website' din document
$item = new stdClass();
$item->id = $id;
$item->website = ['set' => null]; // Setarea câmpului 'website' la null pentru a-l elimina

// Codifică datele în JSON pentru trimitere
$json_data = json_encode([$item]);

// Configurația contextului pentru cererea POST către Solr
$options = array(
    'http' => array(
        'header'  => "Content-type: application/json\r\n" .
                     "Authorization: Basic " . base64_encode("$username:$password") . "\r\n",
        'method'  => 'POST',
        'content' => $json_data
    )
);

$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);

// Verifică rezultatul și tratează erorile
if ($result === FALSE) {
    http_response_code(500); // Internal Server Error
    echo json_encode(["error" => "Failed to connect to Solr"]);
} else {
    $response = json_decode($result, true);
    if (isset($response['error'])) {
        http_response_code(500);
        echo json_encode(["error" => $response['error']['msg']]);
    } else {
        echo json_encode(["success" => "Website field successfully deleted from the document"]);
    }
}
?>
