<?php

require_once __DIR__ . '/../lib/DB.php';
require_once __DIR__ . '/../lib/jwt.php';
require_once __DIR__ . '/../lib/ApiError.php';
require_once __DIR__ . '/../lib/ParamsError.php';
require_once __DIR__ . '/../services/locationService.php';
require_once __DIR__ . '/../services/paypalService.php';

use Rakit\Validation\Validator;
use MarkWilson\XmlToJson\XmlToJsonConverter;


$doBooking = function ($data, $user) {
    $validator = new Validator();
    $validation = $validator->make($data, [
        "pickup_date" => "required|date:Y-m-d",
        "pickup_time" => "required",
        
        "dropoff_date" => "required|date:Y-m-d",
        "dropoff_time" => "required",
        "pickup_location" => "required",

        "dropoff_location" => "required",
        "veh_id" => "required",
        "currency_code" => "required",
        "paypalPaymentId" => "required",
        "total_price" => "required",

        "pickupCountry" =>  "required",
        "dropoffCountry" =>  "required",

        "equipment"                => "array",
        "equipment.*.vendorEquipID"           => "required",
        "equipment.*.amount"           => "required",
    ]);

    $body = '<OTA_VehResRQ xmlns="http://www.opentravel.org/OTA/2003/05" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation = "http://www.opentravel.org/OTA/2003/05 VehResRQ.xsd" >
    <POS>
    <Source>
    <RequestorID Type="5" ID="MOBILE001" />
    </Source>
    </POS><VehResRQCore>
    <VehRentalCore PickUpDateTime="'.$data["pickup_date"].'T'.$data["pickup_time"].'" ReturnDateTime="'.$data["dropoff_date"].'T'.$data["dropoff_time"].'"><PickUpLocation LocationCode="'.$data["pickup_location"].'" />
    <ReturnLocation LocationCode="'.$data["dropoff_location"].'" />
    </VehRentalCore>
    <Customer>
    <Primary>
    <PersonName>
    <NamePrefix>Sr</NamePrefix>
    <GivenName>'.$user["firstname"].'</GivenName>
    <Surname>'.$user["lastname"].'</Surname>
    </PersonName>
    <Telephone />
    <Email>'.$user["emailaddress"].'</Email>
    <Address>
    <StreetNmbr />
    <CityName />
    <PostalCode />
    </Address>
    <CustLoyalty ProgramID="" MembershipID="" />
    </Primary>
    </Customer>
    <VendorPref></VendorPref>
    <VehPref Code="FVMR-8-8486" />
    <SpecialEquipPrefs>
    '.implode("\n",array_map(function($item) {
                return '<SpecialEquipPref vendorEquipID="'.$item["vendorEquipID"].'" Quantity="'.$item["amount"].'"/>';
            }, $data["equipment"])).'
    </SpecialEquipPrefs><PromoDesc></PromoDesc></VehResRQCore><VehResRQInfo/>
    
    <ArrivalDetails FlightNo="IB3154"/>
    <RentalPaymentPref>
    <Voucher Identifier="'.$data["paypalPaymentId"].'">
    
    <PaymentCard CardType="Paypal" CardCode="" CardNumber="1111111111111111111111111"
    ExpireDate="MM/YY" >
    <CardHolderName>'.$user["firstname"].' '.$user["lastname"].'</CardHolderName>
    <AmountPaid>'.$data["total_price"].'</AmountPaid>
    <CurrencyUsed>USD</CurrencyUsed>
    
    </PaymentCard> </RentalPaymentPref></OTA_VehResRQ>';

    $response = Requests::post('https://OTA.right-cars.com/', ["Content-Type" => "application/soap+xml;charset=utf-8"], $body);

    if ($response->success != true) {
        throw new ApiError("We could not create your booking");
    }

    /*$pdo = getDbForGrcgds();

    $stmt = $pdo->prepare('SELECT * from users where username=:emailaddress');
    $stmt->execute([
        'emailaddress' => $user["emailaddress"],
    ]);
    $localUser = $stmt->fetch();

    $stmt = $pdo->prepare('INSERT INTO Bookings (pickupDate, pickupTime, dropoffDate, dropoffTime, pickLocation, dropoffLocation, customerId) VALUES (:pickupDate, :pickupTime, :dropoffDate, :dropoffTime, :pickLocation, :dropoffLocation, :customerId)');
    $stmt->execute([
        "pickupDate" => $data["pickup_date"],
        "pickupTime" => $data["pickup_time"],
        "dropoffDate" => $data["dropoff_date"],
        "dropoffTime" => $data["dropoff_time"],
        "pickLocation" => $data["pickup_location"],
        "dropoffLocation" => $data["dropoff_location"],
        "customerId" => $localUser["id"],
    ]);*/

    $xml = new \SimpleXMLElement($response->body);
    $converter = new XmlToJsonConverter();
    $json = $converter->convert($xml);
    $bookings = json_decode($json, true);

    header('Content-Type: application/json');
    if (!array_key_exists("VehResRSCore",$bookings["OTA_VehResRS"])) {
        echo json_encode([], JSON_PRETTY_PRINT);
    } else {
        echo json_encode($bookings["OTA_VehResRS"]["VehResRSCore"]["VehReservation"], JSON_PRETTY_PRINT);
    }
};

$getBookings = function ($data, $user) {
    $response = Requests::post('https://OTA.right-cars.com/', ["Content-Type" => "application/soap+xml;charset=utf-8"], '<OTA_VehListRQ xmlns="http://www.opentravel.org/OTA/2003/05" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation = "http://www.opentravel.org/OTA/2003/05 VehResRQ.xsd" >
    <POS>
    <Source>
    <RequestorID Type="5" ID="MOBILE001" />
    </Source>
    </POS>
    <Customer>
    <Primary>
    <Email>'.$user["emailaddress"].'</Email>
    </Primary>
    </Customer>
    </OTA_OTA_VehListRQ>');
    if ($response->success != true) {
        throw new ApiError("We could not get your list of bookings");
    }

    $xml = new \SimpleXMLElement($response->body);
    $s = simplexml_load_string($response->body);
    $simpleArray = json_decode(json_encode($s),TRUE);
    if (!array_key_exists("VehResRSCore", $simpleArray)) {
        header('Content-Type: application/json');
        echo json_encode([[]], JSON_PRETTY_PRINT);
        return;
    }
    $simpleArray = $simpleArray["VehResRSCore"];
    $converter = new XmlToJsonConverter();
    $json = $converter->convert($xml);
    $bookings = json_decode($json, true);

    $dataToSend=array();

    if (array_key_exists("VehResRSCore",$bookings["OTA_VehListRS"])) {
        foreach ($bookings["OTA_VehListRS"]["VehResRSCore"] as $value) {
            if (array_key_exists("VehReservation",$value)) {
                $toIterate = $value["VehReservation"]["VehSegmentCore"];
            } else {
                $toIterate = $value["VehSegmentCore"];
            }            

            $toPush = [
                "resnumber" => $toIterate["ConfID"]["Resnumber"],
                "pLocation" => $toIterate["LocationDetails"][0]["Name"],
                "pLocationAddress" => $toIterate["LocationDetails"][0]["Address"]["AddressLine"],
                "pickUpInstructions" => $toIterate["LocationDetails"][0]["Pickupinst"],
                "pPhoneNumber" => $toIterate["LocationDetails"][0]["Telephone"]["PhoneNumber"],
                "rPhoneNumber" => $toIterate["LocationDetails"][1]["Telephone"]["PhoneNumber"],
                "unixPTime" => $toIterate["VehRentalCore"]["PickUpDateTime"],
                "rLocation" => $toIterate["LocationDetails"][1]["Name"],
                "unixRTime" => $toIterate["VehRentalCore"]["ReturnDateTime"],
                "reservationStatus" => $toIterate["ConfID"]["ReservationStatus"],
                "keytype" => $toIterate["ConfID"]["Keytype"],
                "carModel" => $toIterate["ConfID"]["CarModel"],
                "finalCost" => $toIterate["Payment"]["AmountToPayForRental"],
                "payableOnCollection" => $toIterate["Payment"]["PayableOnCollection"],
                "unixTime" => time(),
            ];

            $found = NULL;
            foreach ($simpleArray as $reservation) {
                if (array_key_exists("VehReservation", $reservation)) {
                    $tempArr = $reservation["VehReservation"]["VehSegmentCore"];
                } else {
                    $tempArr = $reservation["VehSegmentCore"];
                }
                foreach ($tempArr as $key => $value) {
                    if ($key == "ConfID" && $value["Resnumber"] == $toIterate["ConfID"]["Resnumber"]) {
                        $found = $reservation;
                    }
                }
            }

            if ($found) {
                if (array_key_exists("VehReservation", $reservation)) {
                    $tempArr = $reservation["VehReservation"]["VehSegmentCore"];
                } else {
                    $tempArr = $reservation["VehSegmentCore"];
                }
                $toPush["pLocationAddress"]["addressName"] = $tempArr["LocationDetails"][0]["Address"]["AddressLine"];
            }


            if (array_key_exists("Description",$toIterate["Payment"])) {
                if (count($toIterate["Payment"]["Description"]) == 2) {
                    $toPush["equipment"] = [$toIterate["Payment"]["Description"]];
                } else {
                    $toPush["equipment"] = $toIterate["Payment"]["Description"];
                }
            } else {
                $toPush["equipment"] = [];
            }

            array_push($dataToSend, $toPush);
            
        }
        header('Content-Type: application/json');
        echo json_encode($dataToSend, JSON_PRETTY_PRINT);
    } else {
        header('Content-Type: application/json');
        echo json_encode([[]], JSON_PRETTY_PRINT);
    }

    

    
};

$sendCancelCode = function ($data, $user) {
    $pdo = getDb();

    $verifyCode = mt_rand(1000,9999);
    
    $pdo
    ->prepare('UPDATE users SET cancellation=:cancellation where id=:id')
    ->execute([
        "cancellation" => $verifyCode,
        "id" => $user['id']
    ]);

    $phoneNumber = $user['mobilecode'] . $user['mobilenumber'];
    $message = 'Your Right Cars cancelation code is ' . $verifyCode;
    Requests::get("https://development.right-cars.com/aphonecontrol/otpsms.php?tele=".$phoneNumber."&message=".$message);
    header('Content-Type: application/json');
    echo json_encode(["success" => "Code Sended!"], JSON_PRETTY_PRINT);
};

$verifyCancelCode = function ($data, $user) {
    $validator = new Validator();
    $validation = $validator->make($data, [
        'code' => 'required',
    ]);

    $validation->validate();

    if ($validation->fails()) {
        $errors = $validation->errors()->all();
        throw new ParamsError($errors);
    }
    $pdo = getDb();

    $stmt = $pdo->prepare('SELECT * from users where cancellation=:cancellation AND id=:id limit 1');
    $stmt->execute([
        'cancellation' => trim($data['code']),
        'id' => $user['id']
    ]);
    if ($stmt->rowCount() == 0) {
        throw new ApiError("Code not found");
    }

    $pdo
    ->prepare('UPDATE users SET cancellation=:cancellation where id=:id')
    ->execute([
        "cancellation" => 0,
        "id" => $user['id']
    ]);

    header('Content-Type: application/json');
    echo json_encode(["success" => "Code Verified!"], JSON_PRETTY_PRINT);
};
