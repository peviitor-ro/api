<?php 
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

class Validator {
    private $validatorEndpoint; // Declarație explicită a proprietății
    private $headers;
    public function __construct($validatorEndpoint)
    {
        $this->validatorEndpoint = $validatorEndpoint;
        $this->headers = array('Content-Type: application/json');
    }
    
    public function post($data) {
        $jsonData = json_encode($data);
        
        $data = json_decode($jsonData, true);
        
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $this->validatorEndpoint);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);
        
      return array(
            'response' => $response,
            'httpCode' => $httpCode
        );
    }
    
    public function setTokenHeaders() {
        $headers = getallheaders();
        
        $token = $headers['Authorization'];
        
        if ($token) {
            $this->headers[] = 'Authorization: ' . $token;
        }
    }
    
}
?>