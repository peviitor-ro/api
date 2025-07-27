<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use Dotenv\Dotenv;

class v4Test extends TestCase
{
    public function testServer()
    {
        // Încarcă variabilele din api.env
        $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
        $dotenv->load();

        if (isset($_ENV['PROD_SERVER'])) {
            echo "PROD_SERVER loaded.";
        } else {
            echo "PROD_SERVER is not loaded.";
        }

        // Verifică dacă PROD_SERVER este definit
        $this->assertArrayHasKey('PROD_SERVER', $_ENV, 'PROD_SERVER is not defined in api.env.');
        $this->assertNotEmpty($_ENV['PROD_SERVER'], 'PROD_SERVER is empty.');

        // Verifică valoarea variabilei
        $expectedServer = 'zimbor.go.ro';
        $this->assertEquals($expectedServer, $_ENV['PROD_SERVER'], 'The value of PROD_SERVER is not accepted.');
    }
}
