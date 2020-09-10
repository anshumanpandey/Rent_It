<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/lib/TwitterAPIExchange.php';
require_once __DIR__ . '/lib/DB.php';
require_once __DIR__ . '/lib/jwt.php';
require_once __DIR__ . '/services/userService.php';

$response = Requests::post("https://api.twitter.com/oauth/access_token?oauth_token=".$_GET['oauth_token']."&oauth_verifier=".$_GET['oauth_verifier']);

$elements = explode("&", $response->body);
$body = [];

foreach ($elements as $v) {
    $keyAndVal = explode("=", $v);
    $body[$keyAndVal[0]] = $keyAndVal[1];
}


$settings = array(
    'oauth_access_token' => $body["oauth_token"],
    'oauth_access_token_secret' => $body["oauth_token_secret"],
    'consumer_key' => "iCywLIPVkJ1QTAshze2Fcftfb",
    'consumer_secret' => "RmiXtGHwnszUeWJ8uOtqOp2bZ429nPrMpzCuUPs61a9kO2CbEq"
);

$requestMethod = 'GET';
$twitter = new TwitterAPIExchange($settings);
$data = $twitter->setGetfield("?include_email=true&include_entities=false&skip_status=true")
    ->buildOauth("https://api.twitter.com/1.1/account/verify_credentials.json", "GET")
    ->performRequest();

    $json = json_decode($data);

    $pdo = getDb();
    
    $stmt = $pdo->prepare('SELECT * from users where username=:email AND socialmedia=:socialmedia limit 1');
    $stmt->execute([
        'email' => $json->email,
        'socialmedia' => 0,
    ]);
    if ($stmt->rowCount() != 0) {
        throw new ApiError("Email already regsitered");
    }

    $stmt = $pdo->prepare('SELECT * from users where username=:email AND socialmedia=:socialmedia limit 1');
    $stmt->execute([
        'email' => $json->email,
        'socialmedia' => 1,
    ]);

    if ($stmt->rowCount() == 0) {
        $pdo
        ->prepare('INSERT INTO users (username,firstName, vemail, vphone,company,vat, socialmedia) VALUES (:username,:firstName,:vemail,:vphone,:company,:vat,:socialmedia)')
        ->execute([
            'username' => $json->email,
            "firstName" => $json->name,
            'vemail' => 1,
            'vphone' => 0,
            "company" => 'NONE',
            "vat" => 'NONE',
            "socialmedia" => 1
        ]);
        $userToSend = [
            'id' => $pdo->lastInsertId()
        ];
    } else {
        $userToSend = getUserDataEntity($stmt->fetch());
    }


    $userData = getUserDataById($userToSend["id"]);

    if ($userData["twoauth"] != 0 && $userData["vemail"] == 1 && $userData["vphone"] == 1) {
        $verifyCode = mt_rand(1000,9999);
        $stmt = $pdo->prepare('UPDATE users SET tempcode=:twoauth where id=:id');
        $stmt->execute(['twoauth' => $verifyCode, 'id' => $userData['id']]);
        $phoneNumber = $userData['mobilecode'] . $userData['mobilenumber'];
        $message = 'Your Righ Cars verification code is ' . $verifyCode;
        Requests::get("https://development.right-cars.com/aphonecontrol/otpsms.php?tele=".$phoneNumber."&message=".$message);
        echo  "<div style='display:none' id='json'>".json_encode($userData, JSON_PRETTY_PRINT)."</div>";
    } else if ($userData["vphone"] != 1) {
        $verifyCode = mt_rand(1000,9999);
        $stmt = $pdo->prepare('UPDATE users SET vphone=:twoauth where id=:id');
        $stmt->execute(['twoauth' => $verifyCode, 'id' => $userData['id']]);
        $phoneNumber = $userData['mobilecode'] . $userData['mobilenumber'];
        $message = 'Your Righ Cars verification code is ' . $verifyCode;
        Requests::get("https://development.right-cars.com/aphonecontrol/otpsms.php?tele=".$phoneNumber."&message=".$message);
        echo  "<div style='display:none' id='json'>".json_encode(array_merge($userData, ["token" => generate_jtw($userData)]), JSON_PRETTY_PRINT)."</div>";
    } else {
        echo  "<div style='display:none' id='json'>".json_encode(array_merge($userData, ["token" => generate_jtw($userData)]), JSON_PRETTY_PRINT)."</div>";
    }
?>

<script type='text/javascript'>
        (function() {
            const el = document.getElementById("json");
            window.ReactNativeWebView.postMessage(el.innerHTML)
            close();
        })();

</script>