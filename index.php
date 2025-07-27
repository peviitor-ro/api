<?php
// index.php at root of api.peviitor.ro

$requestUri = $_SERVER['REQUEST_URI'];

if ($requestUri === '/' || $requestUri === '') {
    // Redirect to your custom docs domain
    header('Location: https://apidoc.peviitor.ro/');
    exit;
}

?>
