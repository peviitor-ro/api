<?php

use PHPUnit\Framework\TestCase;
use Dotenv\Dotenv;

class v4Test extends TestCase
{
    public function testServer()
    {
        // Încarcă variabilele din .env
        $dotenv = Dotenv::createImmutable(__DIR__ . '/..'); // Merge un nivel în sus
        $dotenv->load();

        // Verifică dacă PROD_SERVER este definit
        $this->assertArrayHasKey('PROD_SERVER', $_ENV, 'PROD_SERVER is not defined in .env.');
        $this->assertNotEmpty($_ENV['PROD_SERVER'], 'PROD_SERVER is empty.');

        // Verifică valoarea variabilei
        $expectedServer = 'zimbor.go.ro';
        $this->assertEquals($expectedServer, $_ENV['PROD_SERVER'], 'The value of PROD_SERVER is not accepted.');
    }
}
?>