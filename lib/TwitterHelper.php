<?php

$consumerKey = "iCywLIPVkJ1QTAshze2Fcftfb";
$consumerSecret = 'RmiXtGHwnszUeWJ8uOtqOp2bZ429nPrMpzCuUPs61a9kO2CbEq';

$oauthParams = [
    'oauth_callback' => filter_var('https://www.right-cars.com/mobileapp/twitterCallback.php', FILTER_SANITIZE_URL),
    'oauth_consumer_key' => $consumerKey, // consumer key from your twitter app: https://apps.twitter.com
    'oauth_nonce' => md5(uniqid()),
    'oauth_signature_method' => 'HMAC-SHA1',
    'oauth_timestamp' => time(),
    'oauth_version' => '1.0',
];


$baseURI = 'https://api.twitter.com/oauth/request_token';
$baseString = buildBaseString($baseURI, $oauthParams); // build the base string


$compositeKey   = getCompositeKey($consumerSecret, null); // first request, no request token yet
$oauthParams['oauth_signature'] = base64_encode(hash_hmac('sha1', $baseString, $compositeKey, true)); // sign the base string



function getCompositeKey($consumerSecret, $requestToken){
    return rawurlencode($consumerSecret) . '&' . rawurlencode($requestToken);
}


function buildBaseString($baseURI, $oauthParams){
    $baseStringParts = [];
    ksort($oauthParams);

    foreach($oauthParams as $key => $value){
        $baseStringParts[] = "$key=" . rawurlencode($value);
    }

    return 'POST&' . rawurlencode($baseURI) . '&' . rawurlencode(implode('&', $baseStringParts));
}


function buildAuthorizationHeader(){
    global $oauthParams;
    $authHeader = 'OAuth ';
    $values = [];

	foreach($oauthParams as $key => $value) {
        $values[] = "$key=\"" . rawurlencode( $value ) . "\"";
    }

    $authHeader .= implode(', ', $values);
    return $authHeader;
}

