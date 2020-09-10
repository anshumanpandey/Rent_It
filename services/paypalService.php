<?php 

function getPaypalAccessToken() {

    $response = Requests::post('https://api.sandbox.paypal.com/v1/oauth2/token', [
        'Content-Type' => 'application/x-www-form-urlencoded',
        'Authorization' => 'Basic ' . base64_encode('AbBy2EJkKQpvu6zmf9gaySHsC5UK-mFjwqI_zLxaNCS60V4cIDU4mR7o5LsBtIU8KAjrh4yqdzsu3J_N:EOAfjk4-jQpSRODRe8FEPeg2X29H8fpW6XHxDjMt92kRYbz62xKDU02BIrLDSlfLFFpiFSyuj7BV8Tqw'),
    ],
        [
            "grant_type" => "client_credentials"
        ]);
    if ($response->success != true) {
        throw new ApiError("We fail to fetch the Paypal API.");
    }

    return json_decode($response->body)->access_token;

}

function refound(string $id) {
    $token = getPaypalAccessToken();

    $header = [
        'Content-Type' => 'application/json',
        'Authorization' => 'Bearer ' . $token,
    ];
    $response = Requests::get('https://api.sandbox.paypal.com/v1/payments/sale/'.$id.'/refund', $header, array());
    if ($response->success != true) {
        throw new ApiError("We could not refound you payment. Try again.");
    }

}