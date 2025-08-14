<?php
function getAuthHeader($username, $password) {
    return "Authorization: Basic " . base64_encode("$username:$password") . "\r\n";
}
