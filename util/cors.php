<?php
// Sets CORS headers to allow cross-origin requests
function setCORSHeaders($origin = '*') {
    // Allow requests from any origin or you can specify a domain instead of '*'
    header("Access-Control-Allow-Origin: $origin");

    header("Access-Control-Allow-Headers: $origin");

    header('Content-Type: application/json; charset=utf-8');
    
    // Specify allowed HTTP methods
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS");
    
    // Specify allowed headers clients can send
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    
    // Optional: Cache the preflight response for 1 day to improve performance
    //header("Access-Control-Max-Age: 86400");
}