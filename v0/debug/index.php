<?php

$url = 'http://zimbor.go.ro:8985/solr/jobs/select?q=*:*&wt=json';

// Încercăm să obținem conținutul de la URL folosind file_get_contents
$content = file_get_contents($url);

if ($content === false) {
    // Dacă nu am putut obține conținutul, afișăm un mesaj de eroare
    echo "Nu am putut obține conținutul de la URL-ul specificat.";
} else {
    // Dacă am reușit să obținem conținutul, afișăm conținutul
    echo $content;
}

?>
