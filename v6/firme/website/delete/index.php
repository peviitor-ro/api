<?php
// Lista originilor permise
$allowed_origins = ['https://peviitor-ro.github.io'];

// Determinarea originii cererii
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    http_response_code(403); // Forbidden
    exit('Origin not allowed');
}

// Setează header-urile corespunzătoare pentru CORS
header("Access-Control-Allow-Methods: DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Tratamentul cererilor preflight (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Oprește execuția scriptului după trimiterea headerelor
    http_response_code(200);
    exit;
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
        'header'  => "Content-type: application/json\r\n",
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
