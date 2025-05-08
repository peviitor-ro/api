<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use Dotenv\Dotenv;

class v6Test extends TestCase
{
    public function testServer()
    {
        // Load environment variables from api.env
        $dotenv = Dotenv::createImmutable(__DIR__ . '/..', 'api.env');
        $dotenv->load();

        // Assert that PROD_SERVER is set and not empty
        $this->assertArrayHasKey('PROD_SERVER', $_ENV, 'PROD_SERVER is not defined in api.env.');
        $this->assertNotEmpty($_ENV['PROD_SERVER'], 'PROD_SERVER is empty.');

        // Assert that PROD_SERVER has the expected value
        $expectedServer = 'zimbor.go.ro';
        $this->assertEquals($expectedServer, $_ENV['PROD_SERVER'], 'The value of PROD_SERVER is not accepted.');
    }
}
