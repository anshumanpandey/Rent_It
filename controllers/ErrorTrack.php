<?php
require_once __DIR__ . '/../lib/ApiError.php';
require_once __DIR__ . '/../lib/BuildXmlString.php';
require_once __DIR__ . '/../services/vehicleService.php';

use Rakit\Validation\Validator;

function sendError($data) {
    $response = Requests::post('http://grcgds.com/apipreview/api/error-track', array(), [
        "errorMessage" => $data["errorMessage"],
        "clientTimestamp" => time(),
        "userId" => key_exists("userId", $data) ? $data["userId"]: '',
        "appName" => "Right cars mobile app",
    ]);

    header('Content-Type: application/json');
    echo json_encode($response);
}