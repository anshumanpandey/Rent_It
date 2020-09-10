<?php
require_once __DIR__ . '/../lib/ApiError.php';
require_once __DIR__ . '/../lib/BuildXmlString.php';
require_once __DIR__ . '/../services/vehicleService.php';

use Rakit\Validation\Validator;

$searchVehicle = function ($data, $user) {
    $validator = new Validator;
    $validation = $validator->make($data, [
        'pickup_date' => 'required|date:Y-m-d',
        'pickup_time' => 'required',
        
        'dropoff_date' => 'required|date:Y-m-d',
        'dropoff_time' => 'required',
        'pickup_location' => 'required',

        'dropoff_location' => 'required',
        'age' => 'numeric'
    ]);

    $validation->validate();


    if ($validation->fails()) {
        $errors = $validation->errors()->all();
        throw new ParamsError($errors);
    }

    $pdo = getDbForGrcgds();

    $stmt = $pdo->prepare('SELECT * from users where username=:emailaddress');
    $stmt->execute([
        'emailaddress' => $user["emailaddress"],
    ]);
    $localUser = $stmt->fetch();

    $stmt = $pdo->prepare('INSERT INTO CarSearches (pickupDate, pickupTime, dropoffDate, dropoffTime, pickLocation, dropoffLocation, customerId) VALUES (:pickupDate, :pickupTime, :dropoffDate, :dropoffTime, :pickLocation, :dropoffLocation, :customerId)');
    $stmt->execute([
        "pickupDate" => $data["pickup_date"],
        "pickupTime" => $data["pickup_time"],
        "dropoffDate" => $data["dropoff_date"],
        "dropoffTime" => $data["dropoff_time"],
        "pickLocation" => $data["pickup_location"],
        "dropoffLocation" => $data["dropoff_location"],
        "customerId" => $localUser["id"],
    ]);

    $response = getVehicles($data);

    header('Content-Type: application/json');
    echo json_encode($response);
};

$saveDamageImages = function ($data, $user) {
    if (key_exists("module_name", $data)) {
        unset($data['module_name']);        
    }

    if (count($data) == 0) {
        throw new ApiError("No file passed.");
    }

    $validator = new Validator();
    $validation = $validator->make($data, [
        'file' => 'uploaded_file:0,2000M,jpg,png,jpeg,pdf',
    ]);

    $validation->validate();

    if ($validation->fails()) {
        $errors = $validation->errors()->all();
        throw new ParamsError($errors);
    }

    try {

        if (is_array($data) == false) {
            $data = json_decode($data);
        }
        $countfiles = count($data['files']['name']);

        for($i=0;$i<$countfiles;$i++){
            $target_dir = __DIR__ . '/../cardamage/';
    
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777);
            }
            $arr = explode(".",$data["files"]["name"][$i]);
            $fileType = end($arr);

            $arr2 = explode("-",$data["files"]["name"][$i]);
            $idx = $arr2[0];

            $fileName = $data['resNumber'] ."-". $idx. "-" .time() . "." .$fileType;
            $target_file = $target_dir . $fileName;
            $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

            if (move_uploaded_file($data["files"]["tmp_name"][$i], $target_file)) {
                $grcgds = getDbForGrcgds();
                $stmt = $grcgds
                ->prepare('INSERT INTO damage_images (`image_name`, `resNumber`) VALUES (:image_name, :resNumber)');
                $stmt->execute([
                    "image_name" => $fileName,
                    "resNumber" => $data['resNumber'] ,
                ]);
            } else {
                throw new ApiError("Sorry, there was an error uploading your file.");
            }
        }

        header('Content-Type: application/json');
        echo json_encode(["success"=>true]);
    } catch (Exception $th) {
        throw new ApiError($th->getMessage());
    }

};

$saveSignature = function ($data, $user) {
    if (key_exists("module_name", $data)) {
        unset($data['module_name']);        
    }

    if (count($data) == 0) {
        throw new ApiError("No file passed.");
    }

    $validator = new Validator();
    $validation = $validator->make($data, [
        'resNumber' => 'required',
        'file' => 'uploaded_file:0,2000M,jpg,png,jpeg,pdf',
    ]);

    $validation->validate();

    if ($validation->fails()) {
        $errors = $validation->errors()->all();
        throw new ParamsError($errors);
    }

    try {

        if (is_array($data) == false) {
            $data = json_decode($data);
        }

        if (array_key_exists("file", $data)) {
            $target_dir = __DIR__ . '/../sign/';

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
            $fileName = $data["resNumber"]. "." . $fileType;
            $target_file = $target_dir . $fileName;
            $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

            if (move_uploaded_file($data["file"]["tmp_name"], $target_file)) {
                // echo json_encode(array("status" => 1, "data" => array() ,"msg" => "The file ". basename( $data["name"]). " has been uploaded."));
            } else {
                throw new ApiError("Sorry, there was an error uploading your file.");
            }
        }        

        header('Content-Type: application/json');
        echo json_encode(["success"=>true]);
    } catch (Exception $th) {
        throw new ApiError($th->getMessage());
    }

};

$generatePdf = function ($data, $user) {
    $mpdf = new \Mpdf\Mpdf();
    $m = new Mustache_Engine([
        'escape' => function($value) {
            return $value;
        },
    ]);

    $response = Requests::post('https://OTA.right-cars.com/', ["Content-Type" => "application/xml"], '<OTA_resdetmobRQ xmlns="http://www.opentravel.org/OTA/2003/05"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="http://www.opentravel.org/OTA/2003/05
    resdetmob.xsd">
    <POS>
    <Source>
    <RequestorID Type="1" ID="MOBILE001" ID_Name="RightCars" />
    </Source>
    </POS>
    <VehRetResRQCore>
    <ResNumber Number="'.$data["registratioNumber"].'"/>
    </VehRetResRQCore>
    </OTA_resdetmobRQ>');
    if ($response->success != true) {
        throw new ApiError("We could not generate as pdf");
    }


    $xml = new \SimpleXMLElement($response->body);
    $s = simplexml_load_string($response->body);
    $simpleArray = json_decode(json_encode($s),TRUE);

    $data = array_merge($data, $user);
    $data = array_merge($data, ["signatureUrl" => "http://grcgds.com/mobileapp/sign/".$data["registratioNumber"] .".". $data['image_format']]);
    $data = array_merge($data, ["termsAndConditions" => $simpleArray["VehRetResRSCore"]["VehReservation"]["VehSegmentInfo"]["MainTerms"]["Text"] ]);
    
    $damageImages = glob(__DIR__ . '/../cardamage/'. $data["registratioNumber"] . "*");
    foreach ($damageImages as $idx => $value) {
        $data = array_merge($data, ["damageImageUrl" . ($idx + 1) => "http://grcgds.com/mobileapp/cardamage/".basename($value) ]);
    }


    $mpdf->WriteHTML($m->render(getHtmlAsString('templates/pdf/pdf_page_1.html'), $data));
    $mpdf->AddPage();
    $mpdf->WriteHTML($m->render(getHtmlAsString('templates/pdf/pdf_page_2.html'), $data));
    $mpdf->AddPage();
    $mpdf->WriteHTML($m->render(getHtmlAsString('templates/pdf/pdf_page_3.html'), $data));
    $mpdf->AddPage();
    $mpdf->WriteHTML($m->render(getHtmlAsString('templates/pdf/pdf_page_4.html'), $data));
    $time = date("U");
    $pdfName = "RC-".$data["registratioNumber"]."-".$time.".pdf";

    /*
    TODO: this table does not exis on rightcar_mainwebsite
    Type: PDOException; Message: SQLSTATE[42S02]: Base table or view not found: 1146 Table 'rightcar_mainwebsite.carpictures' doesn't exist; File: /home/rightcar/public_html/mobileapp/controllers/vehicle.php; Line: 218;
    $pdo = getDb();    
    $stmt = $pdo->prepare('INSERT INTO carpictures (filename,description,resnumber,tdate,url) VALUES(:filename,:description,:resnumber,:tdate,:url)');
    $stmt->execute([
        'filename' => $pdfName,
        'description' => 'Car Rental Agreement OutGoing',
        'resnumber' => $data["registratioNumber"],
        'tdate' => $time,
        'url' => "https://right-cars.com/datastore/".$pdfName,
    ]);*/

    $mpdf->Output("pdf/".$pdfName, "F");

    $grcgds = getDbForGrcgds();
    $stmt = $grcgds
        ->prepare('INSERT INTO `car_pdf` (`document_name`) VALUES (:document_name);');
    $stmt->execute([
        "document_name" => $pdfName,
    ]);

};
