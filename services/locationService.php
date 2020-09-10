<?php 
require_once __DIR__ . '/../lib/ApiError.php';
require_once __DIR__ . '/../lib/BuildXmlString.php';

use MarkWilson\XmlToJson\XmlToJsonConverter;

function has_string_keys(array $array) {
    return count(array_filter(array_keys($array), 'is_string')) > 0;
  }

function getLocations() {
    $response = Requests::post('https://grcgds.com/apipreview/api/public/locationCodes');
    if ($response->success != true) {
        throw new ApiError("We could not conect to the locations server");
    }

    return $response->body;
}