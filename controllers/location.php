<?php
require_once __DIR__ . '/../lib/ApiError.php';
require_once __DIR__ . '/../services/locationService.php';

use MarkWilson\XmlToJson\XmlToJsonConverter;

function searchLocation($data) {
    $locations = getLocations();

    $by = NULL;
    if (key_exists("q", $data)) {
        $by = $data["q"];
    }

    header('Content-Type: application/json');
    echo $locations;
}