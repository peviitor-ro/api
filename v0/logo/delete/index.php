<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["error" => "Only GET method is allowed"]);
    exit;
}

require_once '../../config.php';

$core  = 'logo';
$command = '/update?commit=true'; // Adăugăm commit=true pentru a aplica modificările imediat
$url = 'http://' . $server . '/solr/' . $core . $command;

$id = $_GET['id'] ?? '';

if (empty($id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Document ID is required', 'code' => 400]);
    exit;
}

// Payload pentru ștergerea documentului pe baza id-ului
$data = json_encode([
    "delete" => [
        "id" => $id // Id-ul documentului pe care vrem să-l ștergem
    ]
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($data),
    'Authorization: Basic ' . base64_encode("$username:$password")
]);

// Execută cererea cURL
$response = curl_exec($ch);

// Verifică erorile cURL
if (curl_errno($ch)) {
    // Dacă există o eroare de cURL
    echo json_encode(['error' => curl_error($ch)]);
    curl_close($ch);
    exit;
}

// Obține codul HTTP din răspunsul cURL
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Dacă codul de răspuns HTTP este 400 sau mai mare, afisează eroarea
if ($http_code >= 400) {
    echo json_encode([
        'error' => 'Failed to delete document',
        'response' => $response,
        'http_code' => $http_code
    ]);
    exit;
}

// Dacă documentul a fost șters cu succes
echo json_encode([
    'message' => 'Document with ID ' . $id . ' has been successfully deleted.',
    'response' => json_decode($response), // Răspunsul de la Solr pentru confirmare
]);
?>
