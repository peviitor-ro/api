<?php

use PHPUnit\Framework\TestCase;

class ConfigTest3 extends TestCase
{
    private $configFile = 'v3/config.php';

    public function testServer()
    {
        // Verifică dacă fișierul de configurare există înainte de a-l include
        $this->assertFileExists($this->configFile, 'Fișierul de configurare nu există.');

        // Include fișierul de configurare
        include $this->configFile;

        // Verifică dacă variabila $server este definită
        $this->assertNotEmpty($server, 'Variabila $server nu este definită în fișierul de configurare.');
    }
}

?>
