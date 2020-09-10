<?php 
require_once __DIR__ . '/../lib/DB.php';
require_once __DIR__ . '/../lib/Constants.php';
require_once __DIR__ . '/../lib/ApiError.php';

function getUserDataById($id, $conn = NULL) {
    $pdo = $conn ? $conn : getDb();

    $stmt = $pdo->prepare('SELECT * from users where id=:id');
    $stmt->execute(['id' => $id]);
    if ($stmt->rowCount() == 0) {
        throw new ApiError("User not found", 401);
    }
    $user = $stmt->fetch();

    return getUserDataEntity($user);

}

function getUserDataEntity($user) {
    return [
        "id" => $user["id"],
        "emailaddress" => $user["username"],
        "mobilenumber" => $user["mobilenumber"],
        'mobilecode' => $user['mobilecode'],
        'firstname' => $user['firstname'],
        'lastname' => $user['lastname'],
        'add1' => $user['add1'],
        'add2' => $user['add2'],
        'city' => $user['city'],
        "vemail" => $user["vemail"],
        "vphone" => $user["vphone"],
        "country" => $user["country"],
        "postcode" => $user["postcode"],
        
        'passday' => $user["passday"],
        'passmonth' => $user["passmonth"],
        'passyear' => $user["passyear"],
        'passimage' => $user["passimage"],
        'passcountry' => $user["passcountry"],
        'passport' => $user["passport"],

        'drlic' => $user["drlic"],
        'drday' => $user["drday"],
        'drmonth' => $user["drmonth"],
        'dryear' => $user["dryear"],
        'drimage' => $user["drimage"],
        'drcountry' => $user["drcountry"],

        'selfiurl' => $user["selfiurl"],

        "company" => $user['company'],
        "vat" => $user['vat'],

        "twoauth" => $user['twoauth'],

        "vdr" => $user["vdr"],
        "vpass" => $user["vpass"],
        "vself" => $user["vself"],
        "socialmedia" => $user["socialmedia"],
    ];

}

function saveRentItUser($data) {
    $body = [];
    $body["Email"] = $data["username"];
    $body["FirstName"] = $data["firstname"];
    $body["LastName"] = $data["lastname"];
    $body["Address"] = $data["add1"];
    $body["City"] = $data["city"];
    $body["ZIP"] = $data["postcode"];
    $body["CountryCode"] = $data["country"];
    $body["Language"] = "en";
    $body["Phone"] = $data["mobilecode"] . $data["mobilenumber"];
    $body["UserCreateSetPassword"] = true;
    $body["UserCreatePassword"] = $data["password"];

    $headers = array('Content-Type' => 'application/json');
    $url = "https://webapi.rent.it/api-ri/Users/Create/?ClientId={$RENTIT_CLIENTID}&APIKey={$RENTIT_APIKEY}&ConsumerIP={$RENTIT_CONSUMERIP}";
    $response = Requests::post('http://grcgds.com/mobileapp/index.php', $headers, json_encode($body));
    if (!$response->success) {
        throw new ApiError("We could not create user data");
    }
}