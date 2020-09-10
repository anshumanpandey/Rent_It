<?php 
require_once __DIR__ . '/../lib/ApiError.php';
require_once __DIR__ . '/../lib/BuildXmlString.php';

use MarkWilson\XmlToJson\XmlToJsonConverter;

function getVehicles($data) {
    $body = buildXmlString($data);

    $headers = [
        'Content-Type' => 'text/plain; charset=UTF8',
    ];
    $response = Requests::post('https://ota.right-cars.com', $headers, $body);
    if ($response->success != true) {
        throw new ApiError("We could not conect to the locations server");
    }

    $branches = $response->body;

    if ($branches == "") {
        var_dump($branches);
        throw new ApiError("We could not fetch cars with the parameters you send");
    }

    if (preg_match("/Error/i", $branches)) {
        throw new ApiError("We found an error fetching the car list");
    }

    $xml = new \SimpleXMLElement($branches);
    $converter = new XmlToJsonConverter();
    $json = $converter->convert($xml);
    $branchArray = json_decode($json, true);

    return [
        "VehAvailRSCore" => [
            "VehRentalCore" => [
                "PickUpDateTime" => $branchArray["OTA_VehAvailRateRS"]["VehAvailRSCore"]["VehRentalCore"]["-PickUpDateTime"],
                "ReturnDateTime" => $branchArray["OTA_VehAvailRateRS"]["VehAvailRSCore"]["VehRentalCore"]["-ReturnDateTime"],

                "LocationCode" => $branchArray["OTA_VehAvailRateRS"]["VehAvailRSCore"]["VehRentalCore"]["PickUpLocation"]["-LocationCode"],
                "ReturnLocation" => $branchArray["OTA_VehAvailRateRS"]["VehAvailRSCore"]["VehRentalCore"]["ReturnLocation"]["-LocationCode"]
            ],
            "VehVendorAvails" => array_map(function ($VehAvail) {
                return [
                    "Status" => $VehAvail["VehAvailCore"]["-Status"],
                    "VehID" => $VehAvail["VehAvailCore"]["-VehID"],
                    "Deeplink" => $VehAvail["VehAvailCore"]["-Deeplink"],
                    "Vehicle" => [
                        "AirConditionInd" => $VehAvail["VehAvailCore"]["Vehicle"]["-AirConditionInd"],
                        "TransmissionType" => $VehAvail["VehAvailCore"]["Vehicle"]["-TransmissionType"],
                        "VehMakeModel" => [
                            "Name" => $VehAvail["VehAvailCore"]["Vehicle"]["VehMakeModel"]["-Name"],
                            "PictureURL" => $VehAvail["VehAvailCore"]["Vehicle"]["VehMakeModel"]["-PictureURL"],
                        ],
                        "VehType" => [
                            "VehicleCategory"=> $VehAvail["VehAvailCore"]["Vehicle"]["VehType"]["-VehicleCategory"],
                            "DoorCount"=> $VehAvail["VehAvailCore"]["Vehicle"]["VehType"]["-DoorCount"],
                            "Baggage"=> $VehAvail["VehAvailCore"]["Vehicle"]["VehType"]["-Baggage"],
                        ],
                        "VehClass" => [
                            "Size" => $VehAvail["VehAvailCore"]["Vehicle"]["VehClass"]["-Size"],
                        ],
                        "VehTerms" => [
                            "Included" => array_map(function ($Included) { 
                                $arr = [
                                    "code" => $Included["-code"],
                                    "header" => $Included["-header"],
                                    "price" => $Included["-price"],
                                ];

                                if (array_key_exists("-excess", $Included)) {
                                    $arr["excess"] = $Included["-excess"];
                                }

                                if (array_key_exists("-details", $Included)) {
                                    $arr["details"] = $Included["-details"];
                                }

                                return $arr;

                            } ,$VehAvail["VehAvailCore"]["Vehicle"]["VehTerms"]["Included"]),
                            "NotIncluded" => array_map(function ($NotIncluded) { 
                                $arr = [];
                                if (array_key_exists("-excess", $NotIncluded)) {
                                    $arr["excess"] = $NotIncluded["-excess"];
                                }
                                if (array_key_exists("-code", $NotIncluded)) {
                                    $arr["code"] = $NotIncluded["-code"];
                                }
                                if (array_key_exists("-mandatory", $NotIncluded)) {
                                    $arr["mandatory"] = $NotIncluded["-mandatory"];
                                }
                                if (array_key_exists("-header", $NotIncluded)) {
                                    $arr["header"] = $NotIncluded["-header"];
                                }
                                if (array_key_exists("-price", $NotIncluded)) {
                                    $arr["price"] = $NotIncluded["-price"];
                                }
                                if (array_key_exists("-limit", $NotIncluded)) {
                                    $arr["limit"] = $NotIncluded["-limit"];
                                }
                                    
                                return $arr;
                            } ,$VehAvail["VehAvailCore"]["Vehicle"]["VehTerms"]["NotIncluded"])
                        ]
                    ],
                    "RentalRate" => [
                        "RateDistance" => [
                          "Unlimited" => $VehAvail["VehAvailCore"]["RentalRate"]["RateDistance"]["-Unlimited"],
                          "DistUnitName" => $VehAvail["VehAvailCore"]["RentalRate"]["RateDistance"]["-DistUnitName"],
                          "VehiclePeriodName" => $VehAvail["VehAvailCore"]["RentalRate"]["RateDistance"]["-VehiclePeriodName"],
                        ],
                        "RateQualifier" => [
                          "RateCategory" => $VehAvail["VehAvailCore"]["RentalRate"]["RateQualifier"]["-RateCategory"],
                          "RateQualifier" => $VehAvail["VehAvailCore"]["RentalRate"]["RateQualifier"]["-RateQualifier"],
                          "RatePeriod" => $VehAvail["VehAvailCore"]["RentalRate"]["RateQualifier"]["-RatePeriod"],
                          "VendorRateID" => $VehAvail["VehAvailCore"]["RentalRate"]["RateQualifier"]["-VendorRateID"]
                        ]
                    ],
                    "VehicleCharge" => [
                        "Amount" => $VehAvail["VehAvailCore"]["VehicleCharges"]["VehicleCharge"]["-Amount"],
                        "CurrencyCode" => $VehAvail["VehAvailCore"]["VehicleCharges"]["VehicleCharge"]["-CurrencyCode"],
                        "TaxInclusive" => $VehAvail["VehAvailCore"]["VehicleCharges"]["VehicleCharge"]["-TaxInclusive"],
                        "GuaranteedInd" => $VehAvail["VehAvailCore"]["VehicleCharges"]["VehicleCharge"]["-GuaranteedInd"],
                        "Purpose" => $VehAvail["VehAvailCore"]["VehicleCharges"]["VehicleCharge"]["-Purpose"],
                        "TaxAmount" => [
                          "Total" => $VehAvail["VehAvailCore"]["VehicleCharges"]["VehicleCharge"]["TaxAmounts"]["TaxAmount"]["-Total"],
                          "CurrencyCode" => $VehAvail["VehAvailCore"]["VehicleCharges"]["VehicleCharge"]["TaxAmounts"]["TaxAmount"]["-CurrencyCode"],
                          "Percentage" => $VehAvail["VehAvailCore"]["VehicleCharges"]["VehicleCharge"]["TaxAmounts"]["TaxAmount"]["-Percentage"],
                          "Description" => $VehAvail["VehAvailCore"]["VehicleCharges"]["VehicleCharge"]["TaxAmounts"]["TaxAmount"]["-Description"],
                        ],
                        "Calculation" => [
                          "UnitCharge" => $VehAvail["VehAvailCore"]["VehicleCharges"]["VehicleCharge"]["Calculation"]["-UnitCharge"],
                          "UnitName" => $VehAvail["VehAvailCore"]["VehicleCharges"]["VehicleCharge"]["Calculation"]["-UnitName"],
                          "Quantity" => $VehAvail["VehAvailCore"]["VehicleCharges"]["VehicleCharge"]["Calculation"]["-Quantity"],
                          "taxInclusive" => $VehAvail["VehAvailCore"]["VehicleCharges"]["VehicleCharge"]["Calculation"]["-taxInclusive"],
                        ]
                    ],
                    "TotalCharge" => [
                        "RateTotalAmount" => $VehAvail["VehAvailCore"]["TotalCharge"]["-RateTotalAmount"],
                        "CurrencyCode" => $VehAvail["VehAvailCore"]["TotalCharge"]["-CurrencyCode"],
                        "taxInclusive" => $VehAvail["VehAvailCore"]["TotalCharge"]["-taxInclusive"]
                    ],
                    "PricedEquips" => array_map(function($PricedEquip) {
                        return [
                            "Equipment" => [
                              "Description" => $PricedEquip["PricedEquip"]["Equipment"]["-Description"],
                              "EquipType" => $PricedEquip["PricedEquip"]["Equipment"]["-EquipType"],
                              "vendorEquipID" => $PricedEquip["PricedEquip"]["Equipment"]["-vendorEquipID"],
                            ],
                            "Charge" => [
                              "Taxamount" => [
                                "Total" => $PricedEquip["PricedEquip"]["Charge"]["Taxamounts"]["Taxamount"]["Total"],
                                "CurrencyCode" =>  $PricedEquip["PricedEquip"]["Charge"]["Taxamounts"]["Taxamount"]["CurrencyCode"],
                                "Percentage" =>  $PricedEquip["PricedEquip"]["Charge"]["Taxamounts"]["Taxamount"]["Percentage"],
                              ],
                              "Calculation" => [
                                "UnitCharge" => $PricedEquip["PricedEquip"]["Charge"]["Calculation"]["UnitCharge"],
                                "UnitName" => $PricedEquip["PricedEquip"]["Charge"]["Calculation"]["UnitName"],
                                "Quantity" => $PricedEquip["PricedEquip"]["Charge"]["Calculation"]["Quantity"],
                                "TaxInclusive" => $PricedEquip["PricedEquip"]["Charge"]["Calculation"]["TaxInclusive"],
                              ],
                              "Amount" => $PricedEquip["PricedEquip"]["Charge"]["Amount"],
                              "TaxInclusive" => $PricedEquip["PricedEquip"]["Charge"]["TaxInclusive"],
                              "IncludedRate" => $PricedEquip["PricedEquip"]["Charge"]["IncludedRate"],
                              "IncludedInEstTotalInd" => $PricedEquip["PricedEquip"]["Charge"]["IncludedInEstTotalInd"],
                            ]
                        ];
                    } ,$VehAvail["VehAvailCore"]["PricedEquips"])
                ];
            }, $branchArray["OTA_VehAvailRateRS"]["VehVendorAvails"]["VehVendorAvail"]["VehAvails"]["VehAvail"])
        ]
    ];
}

function getDiscoverCarsVehicles($data) {
    $body = [];

    $pickDate = str_replace("-",".",$data["pickup_date"]);
    $pickTime = $data['pickup_time'];
    $dropoffDate = str_replace("-",".",$data["dropoff_date"]);
    $dropoffTime = $data['dropoff_time'];

    $body["DateFrom"] = "{$pickDate}T{$pickTime}";
    $body["DateTo"] = "{$dropoffDate}T{$dropoffTime}";
    $body["PickupLocationID"] = $data['pickup_location'];
    $body["DropOffLocationID"] = $data['dropoff_location'];
    $body["CurrencyCode"] = "EUR";
    $body["Age"] = "35";
    $body["UserIP"] = "192.168.1.1";
    $body["Pos"] = "LV";
    $body["Lng2L"] = "en";
    $body["DeviceTypeID"] = "101";
    $body["DomainExtension"] = "example: .com, .co.uk, co.za, .lv, .de";
    $body["SearchOnlyPartners"] = "null";

    $options = [ 'auth' => new Requests_Auth_Basic(array('mqTqzF7a42zk', 'xks8pgd2QMqAS2qN')) ];
    $headers = array('Content-Type' => 'application/json');
    $url = 'https://api-partner.discovercars.com/api/Aggregator/GetCars?access_token=yHjjy7XZVTsVTb4zP3HLc3uQP3ZJEvBkKBuwWhSwNCkafCXx5ykRmhJdnqW2UJT3';
    Requests::post($url, $headers, json_encode($body), $options);

    if ($response->success != true) {
        throw new ApiError("We could not conect to the locations server");
    }

    $bodyResponse = json_decode($response->body);

    return [
        "VehAvailRSCore" => [
            "VehRentalCore" => [
                "PickUpDateTime" => $data["pickup_date"] . "T" . $data["pickup_time"],
                "ReturnDateTime" => $data["dropoff_date"] . "T" . $data["dropoff_time"],

                "LocationCode" => $data['pickup_location'],
                "ReturnLocation" => $data['dropoff_location']
            ],
            "VehVendorAvails" => array_map(function ($VehAvail) {
                return [
                    "VehID" => $VehAvail["CarUID"],
                    "Deeplink" => $VehAvail["BookingPageUrl"],
                    "Vehicle" => [
                        "AirConditionInd" => $VehAvail["AirCon"],
                        "TransmissionType" => $VehAvail["TransmissionType"] == 1 ? "Automatic": "Manual",
                        "VehMakeModel" => [
                            "Name" => $VehAvail["Name"],
                            "PictureURL" => $VehAvail["VehicleImageUrl"],
                        ],
                        "VehType" => [
                            "VehicleCategory"=> $VehAvail["SIPP"],
                            "DoorCount"=> $VehAvail["Doors"],
                            "Baggage"=> $VehAvail["Bags"],
                        ],
                        "VehClass" => [
                            "Size" => $VehAvail["PasengerCount"],
                        ],
                        "VehTerms" => []
                    ],
                    "RentalRate" => [],
                    "VehicleCharge" => [
                        "CurrencyCode" => $VehAvail["Currency"],
                    ],
                    "TotalCharge" => [
                        "RateTotalAmount" => $VehAvail["Price"],
                        "CurrencyCode" => $VehAvail["Currency"],
                    ],
                    "PricedEquips" => []
                ];
            }, $bodyResponse)
        ]
    ];
}