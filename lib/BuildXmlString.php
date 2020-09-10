<?php

function buildXmlString($parameter) {
    $age = array_key_exists("age",$parameter) ? $parameter["age"] : 30;
    return '<?xml version="1.0"?>
<OTA_VehAvailRateRQDeep xmlns="http://www.opentravel.org/OTA/2003/05" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.opentravel.org/OTA/2003/05OTA_VehAvailRateRQ.xsd" TimeStamp="2010-11-12T11:00:00" Target="Test" Version="1.002">
  <POS>
    <Source>
        <RequestorID Type="5" ID="1000022"/>
    </Source>
  </POS>
  <VehAvailRQCore Status="Available">
  	<Currency Code="EUR"/>
      <VehRentalCore PickUpDateTime="'.$parameter["pickup_date"].'T'.$parameter["pickup_time"].':00" ReturnDateTime="'.$parameter["dropoff_date"].'T'.$parameter["dropoff_time"].':00">
    
      <PickUpLocation LocationCode="'.$parameter["pickup_location"].'"/>

      <ReturnLocation LocationCode="'.$parameter["dropoff_location"].'"/>
    </VehRentalCore>
  </VehAvailRQCore>
  <VehAvailRQInfo>
    <Customer>
      <Primary>
        <CitizenCountryName Code="GB"/>
        <DriverType Age="30"/>
      </Primary>
    </Customer>
    <TPA_Extensions>
      <ConsumerIP>192.168.102.14</ConsumerIP>
    </TPA_Extensions>
  </VehAvailRQInfo>
</OTA_VehAvailRateRQDeep>';
}