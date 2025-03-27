    <?php

    use PHPUnit\Framework\TestCase;
    use Dotenv\Dotenv;

    class v0Test extends TestCase
    {
        public function testServer()
        {
            // Încarcă variabilele din .env
            $dotenv = Dotenv::createImmutable(__DIR__ . '/..'); // Merge un nivel în sus
            $dotenv->load();

            // Verifică dacă LOCAL_SERVER este definit
            $this->assertArrayHasKey('LOCAL_SERVER', $_ENV, 'LOCAL_SERVER is not defined in .env.');
            $this->assertNotEmpty($_ENV['LOCAL_SERVER'], 'LOCAL_SERVER is empty.');

            // Verifică valoarea variabilei
            $expectedServer = '172.18.0.10:8983';
            $this->assertEquals($expectedServer, $_ENV['LOCAL_SERVER'], 'The value of LOCAL_SERVER is not accepted.');
        }
    }
?>