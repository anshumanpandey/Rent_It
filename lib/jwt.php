<?php

use \Firebase\JWT\JWT;

JWT::$leeway = 60; // $leeway in seconds

$KEY = "-/-+w+64fwq9";

function generate_jtw($payload) {
    global $KEY;
    $jwt = JWT::encode($payload, $KEY);
    return $jwt;
}

function decode_jwt($jwt) {
    global $KEY;
    $decoded = JWT::decode($jwt, $KEY, array('HS256'));
    return (array)$decoded;
    
}