<?php
require_once __DIR__ . '/../services/userService.php';

if (!function_exists('getallheaders')) {
    function getallheaders() {
    $headers = [];
    foreach ($_SERVER as $name => $value) {
        if (substr($name, 0, 5) == 'HTTP_') {
            $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
        }
    }
    return $headers;
    }
}

$ProtectedRoute = function($callback, $data) {
    $headerValue = NULL;
    $foundHeader = false;

    foreach (getallheaders() as $name => $value) {
        if ($name == 'Auth' || $name == 'auth') {
            $foundHeader = true;
            $headerValue = $value;
        }
    }
    if ($foundHeader == false) {
        throw new ApiError("Missing Auth header", 401);
    }

    $headerPair = explode(" ", $headerValue);
    if (count($headerPair) != 2) {
        throw new ApiError("Invalid token", 401);
    }

    $tokenType  = $headerPair[0];
    $token  = $headerPair[1];
    if ($tokenType != "Bearer" || !$token || $token == '') {
        throw new ApiError("Invalid token", 401);
    }
    
    try {
        $user = decode_jwt($token);
        //getUserDataById($user["id"]);
        $body = json_encode(array('id' => $user["id"], "module_name" => "USER_EXIST"));
        $response = Requests::post('https://www.right-cars.com/mobileapp/index.php', array('Content-Type' => 'application/json'), $body);
        if (json_decode($response->body)->exist == false) {
            throw new ApiError("User not found", 401);
        }
    } catch (\Throwable $th) {
        throw new ApiError("Invalid token", 401);
    }

    return $callback($data, $user);
};