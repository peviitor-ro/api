<?php

use PHPUnit\Framework\TestCase;

class ConfigTest0 extends TestCase
{
    private $configFile = 'v0/config.php';

    public function testServer()
    {
        // It verifies if config.php exists before including it
        $this->assertFileExists($this->configFile, 'config.php does not exist.');

        // It inludes config.php
        include $this->configFile;

        // It verifies if $server is defined in config.php
        $this->assertNotEmpty($server, '$server is not defined in config.php.');

        // It verifies the value of $server
        $expectedServer = '172.18.0.10:8983';
        $this->assertEquals($expectedServer, $server, 'The value of $server is not accepted.');
    }
}

?>
