<?php
require 'vendor/autoload.php';

use \Firebase\JWT\JWT;

$key = "your_secret_key";

function createJWT($email) {
    global $key;
    $payload = [
        "email" => $email,
        "exp" => time() + (60 * 60)  
    ];
    return JWT::encode($payload, $key);
}

function validateJWT($token) {
    global $key;
    try {
        $decoded = JWT::decode($token, $key, array('HS256'));
        return $decoded;
    } catch (Exception $e) {
        return false;
    }
}
?>
