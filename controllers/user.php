<?php

require_once __DIR__ . '/../lib/DB.php';
require_once __DIR__ . '/../lib/jwt.php';
require_once __DIR__ . '/../lib/ApiError.php';
require_once __DIR__ . '/../lib/ParamsError.php';
require_once __DIR__ . '/../lib/GeneratePassword.php';
require_once __DIR__ . '/../lib/TwitterHelper.php';
require_once __DIR__ . '/../lib/HtmlStringImporter.php';
require_once __DIR__ . '/../services/userService.php';

use Rakit\Validation\Validator;
$validator = new Validator;

$fb = new \Facebook\Facebook([
    'app_id' => '2980272092000101',
    'app_secret' => '560f0232447e3a6e0cce573b554acdd6',
    'default_graph_version' => 'v2.10',
]);

function generateLoginTwitterPage(){

    $headers = array('Authorization' => buildAuthorizationHeader());
    $response = Requests::post("https://api.twitter.com/oauth/request_token", $headers);
    $elements = explode("&", $response->body);
    $body = [];

    foreach ($elements as $v) {
        $keyAndVal = explode("=", $v);
        $body[$keyAndVal[0]] = $keyAndVal[1];
    }

    header('Content-Type: application/json');
    echo json_encode(["url" => "https://api.twitter.com/oauth/authenticate?oauth_token=".$body["oauth_token"]], JSON_PRETTY_PRINT);
}

function loginWithApple($data) {
    $pdo = getDb();
    
    $stmt = $pdo->prepare('SELECT * from users where username=:emailaddress AND socialmedia=:socialmedia limit 1');
    $stmt->execute([
        'emailaddress' => $data["email"],
        'socialmedia' => 0,
    ]);
    if ($stmt->rowCount() != 0) {
        throw new ApiError("Email already regsitered");
    } 

    $stmt = $pdo->prepare('SELECT * from users where username=:emailaddress AND socialmedia=:socialmedia limit 1');
    $stmt->execute([
        'emailaddress' => $data["email"],
        'socialmedia' => 10,
    ]);

    if ($stmt->rowCount() == 0) {
        $pdo
        ->prepare('INSERT INTO users (username,vemail,vphone,company,vat,socialmedia, Ref) VALUES (:username,:vemail,:vphone,:company,:vat,:socialmedia,:Ref)')
        ->execute([
            'username' => $data['email'],
            'vemail' => 1,
            'vphone' => 1,
            "company" => 'NONE',
            "vat" => 'NONE',
            "socialmedia" => 10,
            "Ref" => array_key_exists('refCode', $data) ? $data['refCode'] : 'Hannk',
        ]);
        $userData = getUserDataById($pdo->lastInsertId());
    } else {
        $userData = getUserDataEntity($stmt->fetch());
    }


    if ($userData["twoauth"] != 0 && $userData["vemail"] == 1 && $userData["vphone"] == 1) {
        $verifyCode = mt_rand(1000,9999);
        $stmt = $pdo->prepare('UPDATE users SET tempcode=:twoauth where id=:id');
        $stmt->execute(['twoauth' => $verifyCode, 'id' => $userData['id']]);
        $phoneNumber = $userData['mobilecode'] . $userData['mobilenumber'];
        $message = 'Your Righ Cars verification code is ' . $verifyCode;
        Requests::get("https://development.right-cars.com/aphonecontrol/otpsms.php?tele=".$phoneNumber."&message=".$message);
        header('Content-Type: application/json');
        echo json_encode($userData, JSON_PRETTY_PRINT);
        return;
    }

    if ($userData["vphone"] != 1) {
        $verifyCode = mt_rand(1000,9999);
        $stmt = $pdo->prepare('UPDATE users SET vphone=:twoauth where id=:id');
        $stmt->execute(['twoauth' => $verifyCode, 'id' => $userData['id']]);
        $phoneNumber = $userData['mobilecode'] . $userData['mobilenumber'];
        $message = 'Your Righ Cars verification code is ' . $verifyCode;
        Requests::get("https://development.right-cars.com/aphonecontrol/otpsms.php?tele=".$phoneNumber."&message=".$message);
        header('Content-Type: application/json');
        echo json_encode(array_merge($userData, ["token" => generate_jtw($userData)]), JSON_PRETTY_PRINT);
        return;
    }

    header('Content-Type: application/json');
    echo json_encode(array_merge($userData, ["token" => generate_jtw($userData)]), JSON_PRETTY_PRINT);
}

function loginWithFacebook($data) {
    global $fb;
    $response = $fb->get('/me?fields=name,email,id', $data["token"]);
    $user = $response->getGraphUser();

    $pdo = getDb();
    
    $stmt = $pdo->prepare('SELECT * from users where username=:emailaddress AND socialmedia=:socialmedia limit 1');
    $stmt->execute([
        'emailaddress' => $user["email"],
        'socialmedia' => 0,
    ]);
    if ($stmt->rowCount() != 0) {
        throw new ApiError("Email already regsitered");
    } 

    $stmt = $pdo->prepare('SELECT * from users where username=:emailaddress AND socialmedia=:socialmedia limit 1');
    $stmt->execute([
        'emailaddress' => $user["email"],
        'socialmedia' => 1,
    ]);

    if ($stmt->rowCount() == 0) {
        $pdo
        ->prepare('INSERT INTO users (username,vemail,vphone,company,vat,socialmedia, Ref) VALUES (:username,:vemail,:vphone,:company,:vat,:socialmedia, :Ref)')
        ->execute([
            'username' => $user['email'],
            'vemail' => 1,
            'vphone' => 0,
            "company" => 'NONE',
            "vat" => 'NONE',
            "socialmedia" => 1,
            "Ref" => array_key_exists('refCode', $data) ? $data['refCode'] : 'Hannk',
        ]);
        $userData = getUserDataById($pdo->lastInsertId());
    } else {
        $userData = getUserDataEntity($stmt->fetch());
    }

    saveRemoteFB($data);


    if ($userData["twoauth"] != 0 && $userData["vemail"] == 1 && $userData["vphone"] == 1) {
        $verifyCode = mt_rand(1000,9999);
        $stmt = $pdo->prepare('UPDATE users SET tempcode=:twoauth where id=:id');
        $stmt->execute(['twoauth' => $verifyCode, 'id' => $userData['id']]);
        $phoneNumber = $userData['mobilecode'] . $userData['mobilenumber'];
        $message = 'Your Righ Cars verification code is ' . $verifyCode;
        Requests::get("https://development.right-cars.com/aphonecontrol/otpsms.php?tele=".$phoneNumber."&message=".$message);
        header('Content-Type: application/json');
        echo json_encode($userData, JSON_PRETTY_PRINT);
        return;
    }

    if ($userData["vphone"] != 1) {
        $verifyCode = mt_rand(1000,9999);
        $stmt = $pdo->prepare('UPDATE users SET vphone=:twoauth where id=:id');
        $stmt->execute(['twoauth' => $verifyCode, 'id' => $userData['id']]);
        $phoneNumber = $userData['mobilecode'] . $userData['mobilenumber'];
        $message = 'Your Righ Cars verification code is ' . $verifyCode;
        Requests::get("https://development.right-cars.com/aphonecontrol/otpsms.php?tele=".$phoneNumber."&message=".$message);
        header('Content-Type: application/json');
        echo json_encode(array_merge($userData, ["token" => generate_jtw($userData)]), JSON_PRETTY_PRINT);
        return;
    }

    header('Content-Type: application/json');
    echo json_encode(array_merge($userData, ["token" => generate_jtw($userData)]), JSON_PRETTY_PRINT);
}

function forgotPassword($data) {
    $pdo = getDb();
    
    $stmt = $pdo->prepare('SELECT * from users where username=:username');
    $stmt->execute(['username' => $data['username'] ]);
    if ($stmt->rowCount() == 0) {
        throw new ApiError("Email not found!");
    }

    $newPass = randomPassword();

    $stmt = $pdo->prepare('UPDATE users SET password=:password where username=:username');
    $stmt->execute(['username' => $data['username'], 'password' => $newPass ]);

    $from = "noreply@right-cars.com";
    $to = $data['username'];
    $subject = "Recover Right Cars Password";
    $message = "You new password is " . $newPass;
    $headers = "From:" . $from;
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
    mail($to,$subject,$message, $headers);
}

function login($data) {
    global $validator;

    $validation = $validator->make($data, [
        'username' => 'required',
        'password' => 'required',
    ]);

    $validation->validate();


    if ($validation->fails()) {
        $errors = $validation->errors()->all();
        throw new ParamsError($errors);
    }
    $pdo = getDb();
    
    $stmt = $pdo->prepare('SELECT * from users where username=:username and password =:password limit 1');
    $stmt->execute(['username' => trim($data['username']), 'password' => trim($data['password'])]);
    if ($stmt->rowCount() == 0) {
        throw new ApiError("Email or password incorrect");
    }
    $user = $stmt->fetch();

    $userData = getUserDataEntity($user);
    if ($userData["twoauth"] != 0 && $userData["vemail"] == 1 && $userData["vphone"] == 1) {
        $verifyCode = mt_rand(1000,9999);
        $stmt = $pdo->prepare('UPDATE users SET tempcode=:twoauth where id=:id');
        $stmt->execute(['twoauth' => $verifyCode, 'id' => $userData['id']]);
        $phoneNumber = $userData['mobilecode'] . $userData['mobilenumber'];
        $message = 'Your Righ Cars verification code is ' . $verifyCode;
        Requests::get("https://development.right-cars.com/aphonecontrol/otpsms.php?tele=".$phoneNumber."&message=".$message);
        header('Content-Type: application/json');
        echo json_encode($userData, JSON_PRETTY_PRINT);
        return;
    }

    header('Content-Type: application/json');
    echo json_encode(array_merge($userData, ["token" => generate_jtw($userData)]), JSON_PRETTY_PRINT);
}

function userExist($data) {
    header('Content-Type: application/json');

    try {
        getUserDataById($data["id"]);
        echo json_encode(["exist" => true], JSON_PRETTY_PRINT);
    } catch (Exception $th) {
        echo json_encode(["exist" => false], JSON_PRETTY_PRINT);
    }
}

function register($data) {
    global $validator;

    $validation = $validator->make($data, [
        'username' => 'required',
        'emailaddress' => 'required',
        'tele' => 'required',
        'telecode' => 'required',
        'countryCode' => 'required',
    ]);

    $validation->validate();


    if ($validation->fails()) {
        $errors = $validation->errors()->all();
        throw new ParamsError($errors);
    }

    $pdo = getDbForGrcgds();

    $emailExist = $pdo->prepare('SELECT * from users where username = :emailaddress');
    $emailExist->execute([
        'emailaddress' => $data['username'],
    ]);
    if ($emailExist->rowCount() != 0) {
        throw new ApiError("Email or username already used");
    }

    $verifyCode = mt_rand(1000,9999);
    $randomCode = substr(md5(uniqid(mt_rand(), true)) , 0, 8);

    $dataToSave = [
        'username' => $data['username'],
        'password' => $data['password'],
        'mobilenumber' => $data['tele'],
        'mobilecode' => $data['telecode'],
        "tempcode" => $randomCode,
        "vemail" => 0,
        "vphone" => $verifyCode,
        "country" => $data['countryCode'],
        "company" => array_key_exists('company', $data) == false || $data['company'] == "" ? "NONE": $data['company'],
        "vat" =>  array_key_exists('vat', $data) == false || $data['vat']== "" ? "NONE": $data['vat'],

        "vdr" => 0,
        "vpass" => 0,
        "vself" => 0,
        'socialmedia' => 0,
        "Ref" => array_key_exists('refCode', $data) ? $data['refCode'] : 'Hannk',
    ];

    $pdo
    ->prepare('INSERT INTO users (username,password,mobilenumber, mobilecode,tempcode,vemail,vphone,country,company,vat,vdr,vpass,vself,socialmedia,Ref) VALUES (:username,:password,:mobilenumber,:mobilecode,:tempcode,:vemail,:vphone,:country,:company,:vat,:vdr,:vpass,:vself,:socialmedia,:Ref)')
    ->execute($dataToSave);

    $phoneNumber = $data['telecode'] . $data['tele'];
    $message = 'Your Righ Cars verification code is ' . $verifyCode;
    Requests::get("https://development.right-cars.com/aphonecontrol/otpsms.php?tele=".$phoneNumber."&message=".$message);

    $m = new Mustache_Engine([
        'escape' => function($value) {
            return $value;
        },
    ]);
    $template = getHtmlAsString('templates/EmailTemplate.html');
    $templateData = ['verificationUrl' => 'https://www.right-cars.com/mobileapp/index.php?module_name=RENDER_VERIFICATION_EMAIL&code='.$randomCode];
    $message = $m->render($template, $templateData);

    $from = "noreply@right-cars.com";
    $to = $data['username'];
    $subject = "Right Cars Verification Link";
    $headers = "From:" . $from . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
    mail($to,$subject,$message, $headers);
    
    $userData = getUserDataById($pdo->lastInsertId(), $pdo);

    header('Content-Type: application/json');
    echo json_encode(array_merge($userData, ["token" => generate_jtw($userData)]), JSON_PRETTY_PRINT);
}

$editProfile = function ($data, $user) {
    $pdo = getDb();
    
    $stmt = $pdo->prepare('SELECT * from users where id=:id limit 1');
    $stmt->execute(['id' => trim($user['id'])]);
    if ($stmt->rowCount() == 0) {
        throw new ApiError("User not found");
    }
    $userData = $stmt->fetch();
    $dataToUpdate = [
        "emailaddress" => array_key_exists('emailaddress', $data) ? $data['emailaddress'] : $user["emailaddress"],
        "country" => array_key_exists('countryCode', $data) ? $data['countryCode'] : $user["country"],
        'firstname' => array_key_exists('firstname', $data) ? $data['firstname'] : $user["firstname"],
        'lastname' => array_key_exists('lastname', $data) ? $data['lastname'] : $user["lastname"],
        'mobilecode' => array_key_exists('mobilecode', $data) ? $data['mobilecode'] : $user["mobilecode"],
        'mobilenumber' => array_key_exists('mobilenumber', $data) ? $data['mobilenumber'] : $user["mobilenumber"],
        'add1' => array_key_exists('add1', $data) ? $data['add1'] : $user["add1"],
        'add2' => array_key_exists('add2', $data) ? $data['add2'] : $user["add2"],
        'city' => array_key_exists('city', $data) ? $data['city'] : $user["city"],
        'postcode' => array_key_exists('postcode', $data) ? $data['postcode'] : $user["postcode"],
        'twoauth' => array_key_exists('twoauth', $data) ? $data['twoauth'] : $user["twoauth"],
        'vemail' => array_key_exists('vemail', $data) ? $data['vemail'] : $user["vemail"],
        'vphone' => array_key_exists('vphone', $data) ? $data['vphone'] : $user["vphone"],
        'tempcode' => '',
        "company" => $data['company'] == "" ? "NONE": $data['company'],
        "vat" => $data['vat']== "" ? "NONE": $data['vat'],
        'id' => $user['id'],
    ];

    if ($user["emailaddress"] != $data["emailaddress"]) {
        $randomCode = substr(md5(uniqid(mt_rand(), true)) , 0, 8);
        $dataToUpdate['tempcode'] = $randomCode;
        $dataToUpdate['vemail'] = 0;

        $m = new Mustache_Engine([
            'escape' => function($value) {
                return $value;
            },
        ]);
        $template = getHtmlAsString('templates/EmailTemplate.html');
        $values = ['verificationUrl' => 'https://www.right-cars.com/mobileapp/index.php?module_name=RENDER_VERIFICATION_EMAIL&code='.$randomCode];
        $message = $m->render($template, $values);
    
        $from = "noreply@right-cars.com";
        $to = $data['emailaddress'];
        $subject = "Right Cars Verification Link";
        $headers = "From:" . $from . "\r\n";;
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
        mail($to,$subject,$message, $headers);
    }
    if (($user["mobilecode"] != $data["mobilecode"]) || ($user["mobilenumber"] != $data["mobilenumber"])) {
        $verifyCode = mt_rand(1000,9999);
        $dataToUpdate['vphone'] = $verifyCode;

        $phoneNumber = $data['mobilecode'] . $data['mobilenumber'];
        $message = 'Your Right Cars verification code is ' . $verifyCode;
        Requests::get("https://development.right-cars.com/aphonecontrol/otpsms.php?tele=".$phoneNumber."&message=".$message);
    }
    if ($dataToUpdate['vphone'] == 1){
        $dataToUpdate['twoauth'] = (int)$data['twoauth'];
    }
    $query = "UPDATE users SET username=:emailaddress, country=:country,firstname=:firstname, lastname=:lastname, mobilecode=:mobilecode, mobilenumber=:mobilenumber, add1=:add1, add2=:add2, city=:city, postcode=:postcode, twoauth=:twoauth,company=:company,vat=:vat,vemail=:vemail,vphone=:vphone,tempcode=:tempcode WHERE id = :id";

    $statement = $pdo
        ->prepare($query)
        ->execute($dataToUpdate);


    $userData = getUserDataById($data['id']);

    header('Content-Type: application/json');
    echo json_encode(array_merge($userData, ["token" => generate_jtw($userData)]), JSON_PRETTY_PRINT);
};

function verifyOpt($data) {
    global $validator;

    $validation = $validator->make($data, [
        'code' => 'required|numeric',
    ]);

    $validation->validate();

    if ($validation->fails()) {
        $errors = $validation->errors()->all();
        throw new ParamsError($errors);
    }

    $pdo = getDb();
    
    $stmt = $pdo->prepare('SELECT * from users where tempcode=:code limit 1');
    $stmt->execute(['code' => $data['code']]);
    if ($stmt->rowCount() == 0) {
        throw new ApiError("Code not found");
    }

    $user = $stmt->fetch();

    $statement = $pdo
        ->prepare("UPDATE users SET tempcode=:twoauth WHERE id=:id")
        ->execute([
            'twoauth' => 1,
            'id' => $user["id"],
        ]);


    $userData = getUserDataById($user["id"]);

    header('Content-Type: application/json');
    echo json_encode(array_merge($userData, ["token" => generate_jtw($userData)]), JSON_PRETTY_PRINT);
}

function resendVerifyOpt($data) {
    global $validator;

    $validation = $validator->make($data, [
        'id' => 'required'
    ]);

    $validation->validate();

    if ($validation->fails()) {
        $errors = $validation->errors()->all();
        throw new ParamsError($errors);
    }

    $pdo = getDb();
    
    $stmt = $pdo->prepare('SELECT * from users where id=:id limit 1');
    $stmt->execute(['id' => trim($data['id'])]);
    if ($stmt->rowCount() == 0) {
        throw new ApiError("User not found");
    }

    $user = $stmt->fetch();

    if ($user["twoauth"] == 0) {
        throw new ApiError("Opt not enabled");
    }
    if ($user["vemail"] == 0) {
        throw new ApiError("User email not verified");
    }
    if ($user["vphone"] == 0) {
        throw new ApiError("User phone not verified");
    }
    if (!$user["mobilecode"] || $user["mobilecode"] == "" || !$user["mobilenumber"] && $user["mobilenumber"] == "") {
        throw new ApiError("Invalid phone number");
    }

    $verifyCode = mt_rand(1000,9999);
    
    $pdo
    ->prepare('UPDATE users SET tempcode=:vphone where id=:id')
    ->execute([
        "vphone" => $verifyCode,
        "id" => $user['id']
    ]);

    $phoneNumber = $user['mobilecode'] . $user['mobilenumber'];
    $message = 'Your Right Cars verification code is ' . $verifyCode;
    Requests::get("https://development.right-cars.com/aphonecontrol/otpsms.php?tele=".$phoneNumber."&message=".$message);

    header('Content-Type: application/json');
    echo json_encode(["sucess" => "Code Sended"], JSON_PRETTY_PRINT);
}

function verifyProfile($data) {
    global $validator;

    $validation = $validator->make($data, [
        'code' => 'required|numeric',
    ]);

    $validation->validate();

    if ($validation->fails()) {
        $errors = $validation->errors()->all();
        throw new ParamsError($errors);
    }

    $pdo = getDb();
    
    $stmt = $pdo->prepare('SELECT * from users where vphone=:code limit 1');
    $stmt->execute(['code' => $data['code']]);
    if ($stmt->rowCount() == 0) {
        throw new ApiError("Code not found");
    }

    $user = $stmt->fetch();

    /*
    since we need to set phone as verified when using apple login even when we dont have the user phone number
    we are allowing in to verify the number even if is already verified
    if ($user["vphone"] == 1) {
        throw new ApiError("User already verified");
    }*/

    $statement = $pdo
        ->prepare("UPDATE users SET vphone=:vphone WHERE id=:id")
        ->execute([
            'vphone' => 1,
            'id' => $user["id"],
        ]);

    header('Content-Type: application/json');
    echo json_encode(["sucess" => "User verified!"], JSON_PRETTY_PRINT);
}

function resendCode($data) {
    global $validator;

    $validation = $validator->make($data, [
        'id' => 'required'
    ]);

    $validation->validate();

    if ($validation->fails()) {
        $errors = $validation->errors()->all();
        throw new ParamsError($errors);
    }

    $pdo = getDb();
    
    $stmt = $pdo->prepare('SELECT * from users where id=:id limit 1');
    $stmt->execute(['id' => trim($data['id'])]);
    if ($stmt->rowCount() == 0) {
        throw new ApiError("User not found");
    }

    $user = $stmt->fetch();

    if ($user["vphone"] == 1) {
        throw new ApiError("User already verified");
    }
    if (!$user["mobilecode"] || $user["mobilecode"] == "" || !$user["mobilenumber"] && $user["mobilenumber"] == "") {
        throw new ApiError("Invalid phone number");
    }

    $verifyCode = mt_rand(1000,9999);
    
    $pdo
    ->prepare('UPDATE users SET vphone=:vphone where id=:id')
    ->execute([
        "vphone" => $verifyCode,
        "id" => $user['id']
    ]);

    $phoneNumber = $user['mobilecode'] . $user['mobilenumber'];
    $message = 'Your Right Cars verification code is ' . $verifyCode;
    Requests::get("https://development.right-cars.com/aphonecontrol/otpsms.php?tele=".$phoneNumber."&message=".$message);

    header('Content-Type: application/json');
    echo json_encode(["sucess" => "Code Sended"], JSON_PRETTY_PRINT);
}

function resendEmail($data) {
    global $validator;

    $validation = $validator->make($data, [
        'id' => 'required'
    ]);

    $validation->validate();

    if ($validation->fails()) {
        $errors = $validation->errors()->all();
        throw new ParamsError($errors);
    }

    $pdo = getDb();
    
    $stmt = $pdo->prepare('SELECT * from users where id=:id limit 1');
    $stmt->execute(['id' => trim($data['id'])]);
    if ($stmt->rowCount() == 0) {
        throw new ApiError("User not found");
    }

    $user = $stmt->fetch();

    if ($user["vemail"] == 1) {
        throw new ApiError("User email already verified");
    }

    $randomCode = substr(md5(uniqid(mt_rand(), true)) , 0, 8);
    $stmt = $pdo->prepare('UPDATE users SET tempcode=:tempcode where id=:id');
    $stmt->execute([
        'tempcode' => $randomCode,
        'id' => trim($data['id']),
    ]);

    $m = new Mustache_Engine([
        'escape' => function($value) {
            return $value;
        },
    ]);
    $template = getHtmlAsString('templates/EmailTemplate.html');
    $data = ['verificationUrl' => 'https://www.right-cars.com/mobileapp/index.php?module_name=RENDER_VERIFICATION_EMAIL&code='.$randomCode];
    $message = $m->render($template, $data);

    $from = "noreply@right-cars.com";
    $to = $user['username'];
    $subject = "Right Cars Verification Link";
    $headers = "From:" . $from . "\r\n";;
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
    mail($to,$subject,$message, $headers);

    header('Content-Type: application/json');
    echo json_encode(["sucess" => "Verification link sended"], JSON_PRETTY_PRINT);
}

function renderVerificationEmail($data) {
    global $validator;

    $validation = $validator->make($data, [
        'code' => 'required'
    ]);

    $validation->validate();

    if ($validation->fails()) {
        $errors = $validation->errors()->all();
        throw new ParamsError($errors);
    }

    $pdo = getDb();
    
    $stmt = $pdo->prepare('SELECT * from users where tempcode=:tempcode');
    $stmt->execute(['tempcode' => $data["code"]]);
    $user = $stmt->fetch();

    if ($stmt->rowCount() == 0) {
        throw new ApiError("User not found");
    }


    if ($user["vemail"] == 1) {
        throw new ApiError("User email already verified");
    }


    $stmt = $pdo->prepare('UPDATE users SET tempcode=:tempcode, vemail=:vemail where id=:id');
    $stmt->execute([
        'tempcode' => "",
        'vemail' => 1,
        'id' => $user['id'],
    ]);

    echo getHtmlAsString('templates/SuccessVerificationEmail.html');;
}

