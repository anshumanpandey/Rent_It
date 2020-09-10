<?php

require_once __DIR__ . '/../lib/DB.php';
require_once __DIR__ . '/../lib/jwt.php';
require_once __DIR__ . '/../lib/ApiError.php';
require_once __DIR__ . '/../lib/ParamsError.php';
require_once __DIR__ . '/../services/locationService.php';
require_once __DIR__ . '/../services/paypalService.php';
require_once __DIR__ . '/../services/userService.php';

use Rakit\Validation\Validator;

$saveFile = function ($data, $user) {
    if (key_exists("module_name", $data)) {
        unset($data['module_name']);        
    }

    if (count($data) == 0) {
        throw new ApiError("No file passed.");
    }

    $validator = new Validator();
    $validation = $validator->make($data, [
        'file' => 'uploaded_file:0,2000M,jpg,png,jpeg,pdf',
        'fileType' => 'required|in:Passport,Driving License,Selfie',
        'expDate' => 'date',
    ]);

    $validation->validate();

    if ($validation->fails()) {
        $errors = $validation->errors()->all();
        throw new ParamsError($errors);
    }

    $pdo = getDb();

    try {
        //TODO: should we not allow to upload the same file category (passport, driver license) if already exist
        /*$stmt = $pdo->prepare("SELECT * from carpictures where description=:description AND userId = :userId");

        foreach($data as $fileCategory => $data) {
            $stmt->execute([
                'description' => $fileCategory,
                'userId' => $user["id"],
            ]);
            if ($stmt->fetchAll() != 0) {
                throw new ApiError("You already submit and ".$fileCategory.".");
            }
        }*/

        if (is_array($data) == false) {
            $data = json_decode($data);
        }

        if ($data["fileType"] != "Selfie") {
            $dateUnits = explode("-", $data["expDate"]);

            $passportQuery = "UPDATE users set passday=:passday, passmonth=:passmonth, passyear=:passyear, passcountry=:passcountry, passport=:passport where id=:userid";
            $passportData = [
                'passday' => $dateUnits[2],
                'passmonth' => $dateUnits[1],
                'passyear' => $dateUnits[0],
                'passcountry' => $data["filecountry"],
                'passport' => $data["docNumber"],
                'userid' => $user["id"],
            ];

            $licenseQuery = "UPDATE users set drday=:drday, drmonth=:drmonth, dryear=:dryear, drcountry=:drcountry, drlic=:drlic where id=:userid";
            $licenseData = [
                'drday' => $dateUnits[2],
                'drmonth' => $dateUnits[1],
                'dryear' => $dateUnits[0],
                'drcountry' => $data["filecountry"],
                'drlic' => $data["docNumber"],
                'userid' => $user["id"],
            ];
        }

        if (array_key_exists("file", $data)) {
            if ($data["fileType"] == "Passport") {
                $target_dir = __DIR__ . '/../../uploads/pass/';
            }
    
            if ($data["fileType"] == "Driving License") {
                $target_dir = __DIR__ . '/../../uploads/drlic/';                
            }

            if ($data["fileType"] == "Selfie") {
                $target_dir = __DIR__ . '/../../uploads/selfi/';
            }

            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777);
            }
            if (is_array($data["file"])) {
                if (array_key_exists("name" ,$data["file"])) {
                    $arr = explode(".",$data["file"]["name"]);
                    $fileType = end($arr);
                }
            } else {
                $fileType = 'jpg';
            }
            $uuid = uniqid();
            $fileName = $uuid. "." . $fileType;
            $target_file = $target_dir . $fileName;
            $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

            if (isset($dateUnits)) {
                $passportQuery = "UPDATE users set passday=:passday, passmonth=:passmonth, passyear=:passyear, passimage=:passimage, passcountry=:passcountry, passport=:passport where id=:userid";
                $passportData = [
                    'passday' => $dateUnits[2],
                    'passmonth' => $dateUnits[1],
                    'passyear' => $dateUnits[0],
                    'passimage' => $fileName,
                    'passcountry' => $data["filecountry"],
                    'passport' => $data["docNumber"],
                    'userid' => $user["id"],
                ];

                $licenseQuery = "UPDATE users set drday=:drday, drmonth=:drmonth, dryear=:dryear, drimage=:drimage, drcountry=:drcountry, drlic=:drlic where id=:userid";
                $licenseData = [
                    'drday' => $dateUnits[2],
                    'drmonth' => $dateUnits[1],
                    'dryear' => $dateUnits[0],
                    'drimage' => $fileName,
                    'drcountry' => $data["filecountry"],
                    'drlic' => $data["docNumber"],
                    'userid' => $user["id"],
                ];
            }


            $stmt = $pdo->prepare("SELECT * from users where id=:id");
            $stmt->execute([ "id" => $user["id"]]);
            $currentUserData = $stmt->fetch();

            if ($data["fileType"] == "Selfie") {
                if (is_file($target_dir . $currentUserData["selfiurl"])) {
                    unlink($target_dir . $currentUserData["selfiurl"]);
                }
                $stmt = $pdo
                    ->prepare("UPDATE users set selfiurl=:selfiurl where id=:userid")
                    ->execute([
                        'selfiurl' => $fileName,
                        'userid' => $user["id"],
                    ]);
            }
            if (move_uploaded_file($data["file"]["tmp_name"], $target_file)) {
                // echo json_encode(array("status" => 1, "data" => array() ,"msg" => "The file ". basename( $data["name"]). " has been uploaded."));
            } else {
                throw new ApiError("Sorry, there was an error uploading your file.");
            }
        }        

        if ($data["fileType"] == "Passport") {
            if (isset($currentUserData)) {
                if (is_file($target_dir . $currentUserData["passimage"])) {
                    unlink($target_dir . $currentUserData["passimage"]);
                }
            }
            $stmt = $pdo
                ->prepare($passportQuery)
                ->execute($passportData);
        }

        if ($data["fileType"] == "Driving License") {
            if (isset($currentUserData)) {
                if (is_file($target_dir . $currentUserData["drimage"])) {
                    unlink($target_dir . $currentUserData["drimage"]);
                }
            }
            $stmt = $pdo
                ->prepare($licenseQuery)
                ->execute($licenseData);
        }

        $userData = getUserDataById($user['id']);

        header('Content-Type: application/json');
        echo json_encode($userData);
    } catch (Exception $th) {
        throw new ApiError($th->getMessage());
    }

};

$getDocuments = function ($data, $user) {
    /*$files = array_diff(scandir(__DIR__ . '/../docs/'), array('.', '..','.keep'));

    $userFiles = array_filter($files, function($k) use ($user) {
        $fileNameParts = explode("-",$k);
        return $fileNameParts[0] == $user["id"];
    });

    $mappedArray = [];
    foreach ($userFiles as $item) {
        $fileNameParts = explode("-",$item);
        $category = explode(".", $fileNameParts[1]);
        $mappedArray[][$category[0]] = $item;
    }*/

    $pdo = getDb();

    $stmt = $pdo->prepare("SELECT * from users where id=:userid");
    $stmt->execute(['userid' => $user["id"]]);


    header('Content-Type: application/json');
    //echo json_encode($stmt->fetch());
    echo json_encode([]);
};

