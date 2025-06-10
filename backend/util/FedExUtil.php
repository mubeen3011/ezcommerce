<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 12/4/2019
 * Time: 11:36 AM
 */
namespace backend\util;

use common\models\Couriers;
use FedEx\AddressValidationService\ComplexType\AddressToValidate;
use FedEx\AddressValidationService\ComplexType\AddressValidationRequest;
use FedEx\OpenShipService\ComplexType\ConfirmOpenShipmentRequest;
use FedEx\OpenShipService\ComplexType\CreateOpenShipmentRequest;
use FedEx\OpenShipService\ComplexType\RetrieveOpenShipmentRequest;
use FedEx\OpenShipService\ComplexType\ShippingDocument;
use FedEx\OpenShipService\SimpleType\DropoffType;
use FedEx\OpenShipService\SimpleType\LabelFormatType;
use FedEx\OpenShipService\SimpleType\LabelStockType;
use FedEx\OpenShipService\SimpleType\PackagingType;
use FedEx\OpenShipService\SimpleType\ServiceType;
use FedEx\OpenShipService\SimpleType\ShippingDocumentImageType;
use FedEx\RateService\ComplexType\RateRequest;
use FedEx\RateService\ComplexType\RequestedPackageLineItem;
use FedEx\RateService\ComplexType\RequestedShipment;
use FedEx\RateService\SimpleType\LinearUnits;
use FedEx\RateService\SimpleType\PaymentType;
use FedEx\RateService\SimpleType\RateRequestType;
use FedEx\RateService\SimpleType\WeightUnits;
use FedEx\TrackService\ComplexType\TrackRequest;
use FedEx\TrackService\ComplexType\TrackSelectionDetail;
use FedEx\TrackService\SimpleType\TrackIdentifierType;
use yii\base\ErrorException;
use yii\db\Exception;
use yii\web\Request;

class FedExUtil{

    // User credentials
    private static $FEDEX_KEY;
    private static $FEDEX_PASSWORD;

    // Client Detail
    private static $FEDEX_ACCOUNT_NUMBER;
    private static $FEDEX_METER_NUMBER;

    // Environment
    private static $ENV;

    public static function SetCredentials($courierId){

        $Courier = Couriers::find()->where(['id'=>$courierId])->one();

        $Config = json_decode($Courier->configuration,1);

        self::$FEDEX_ACCOUNT_NUMBER = $Config['FEDEX_ACCOUNT_NUMBER'];
        self::$FEDEX_KEY = $Config['FEDEX_KEY'];
        self::$FEDEX_PASSWORD = $Config['FEDEX_PASSWORD'];
        self::$FEDEX_METER_NUMBER = $Config['FEDEX_METER_NUMBER'];
        self::$ENV = $Config['ENVIRONMENT'];

    }

    public static function AddressValidation( $courierId, $CustomerInfo ){


        self::SetCredentials($courierId);

        $addressValidationRequest = new AddressValidationRequest();

        // User Credentials
        $addressValidationRequest->WebAuthenticationDetail->UserCredential->Key = self::$FEDEX_KEY;
        $addressValidationRequest->WebAuthenticationDetail->UserCredential->Password = self::$FEDEX_PASSWORD;

        // Client Detail
        $addressValidationRequest->ClientDetail->AccountNumber = self::$FEDEX_ACCOUNT_NUMBER;
        $addressValidationRequest->ClientDetail->MeterNumber = self::$FEDEX_METER_NUMBER;

        // Version
        $addressValidationRequest->Version->ServiceId = 'aval';
        $addressValidationRequest->Version->Major = 4;
        $addressValidationRequest->Version->Intermediate = 0;
        $addressValidationRequest->Version->Minor = 0;

        // Address(es) to validate.
        $addressValidationRequest->AddressesToValidate = [new AddressToValidate()]; // just validating 1 address in this example.
        $addressValidationRequest->AddressesToValidate[0]->Address->StreetLines = [$CustomerInfo[0]['customer_address']];
        $addressValidationRequest->AddressesToValidate[0]->Address->City = $CustomerInfo[0]['customer_city'];
        $addressValidationRequest->AddressesToValidate[0]->Address->StateOrProvinceCode = HelpUtil::getCountryStateShortCode($CustomerInfo[0]['customer_state']);
        $addressValidationRequest->AddressesToValidate[0]->Address->PostalCode = $CustomerInfo[0]['customer_postcode'];
        $addressValidationRequest->AddressesToValidate[0]->Address->CountryCode = HelpUtil::getCountryStateShortCode($CustomerInfo[0]['customer_country']);
        try {
            $request = new \FedEx\AddressValidationService\Request();
            $addressValidationReply = $request->getAddressValidationReply($addressValidationRequest,true);
            $Address = $addressValidationReply;
            return $Address;

        } catch (\SoapFault $er) {
            return $er->getMessage();
        }


    }

    public static function RateRequest( $courierId, $CustomerInfo , $WarehouseInfo, $Items, $package_weight, $one_rate, $serviceType, $packageOption, $shipDate){

        self::SetCredentials($courierId);
        $shipDate = new \DateTime($shipDate);
        $rateRequest = new RateRequest();

//authentication & client details
        $rateRequest->WebAuthenticationDetail->UserCredential->Key = self::$FEDEX_KEY;
        $rateRequest->WebAuthenticationDetail->UserCredential->Password = self::$FEDEX_PASSWORD;
        $rateRequest->ClientDetail->AccountNumber = self::$FEDEX_ACCOUNT_NUMBER;
        $rateRequest->ClientDetail->MeterNumber = self::$FEDEX_METER_NUMBER;

        $rateRequest->TransactionDetail->CustomerTransactionId = 'RateServiceRequest';

//version
        $rateRequest->Version->ServiceId = 'crs';
        $rateRequest->Version->Major = 24;
        $rateRequest->Version->Minor = 0;
        $rateRequest->Version->Intermediate = 0;

        $rateRequest->ReturnTransitAndCommit = true;

//shipper
        $rateRequest->RequestedShipment->PreferredCurrency = 'USD';
        $rateRequest->RequestedShipment->Shipper->Address->StreetLines = [$WarehouseInfo[0]['address']];
        $rateRequest->RequestedShipment->Shipper->Address->City = $WarehouseInfo[0]['city'];
        $rateRequest->RequestedShipment->Shipper->Address->StateOrProvinceCode = HelpUtil::getCountryStateShortCode($WarehouseInfo[0]['state']);
        $rateRequest->RequestedShipment->Shipper->Address->PostalCode = $WarehouseInfo[0]['zipcode'];
        $rateRequest->RequestedShipment->Shipper->Address->CountryCode = HelpUtil::getCountryStateShortCode($WarehouseInfo[0]['country']);

//recipient
        $rateRequest->RequestedShipment->Recipient->Address->StreetLines = [$CustomerInfo[0]['customer_address']];
        $rateRequest->RequestedShipment->Recipient->Address->City = /*'Cohoes'*/$CustomerInfo[0]['customer_city'];
        $rateRequest->RequestedShipment->Recipient->Address->StateOrProvinceCode = /*'NY'*/HelpUtil::getCountryStateShortCode($CustomerInfo[0]['customer_state']);
        $rateRequest->RequestedShipment->Recipient->Address->PostalCode = /*'12047'*/$CustomerInfo[0]['customer_postcode'];
        $rateRequest->RequestedShipment->Recipient->Address->CountryCode = /*'US'*/HelpUtil::getCountryStateShortCode($CustomerInfo[0]['customer_country']);

        //shipper
        //$rateRequest->RequestedShipment->PreferredCurrency = 'USD';
        //$rateRequest->RequestedShipment->Shipper->Address->StreetLines = ['10 Fed Ex Pkwy'];
        //$rateRequest->RequestedShipment->Shipper->Address->City = 'Memphis';
        //$rateRequest->RequestedShipment->Shipper->Address->StateOrProvinceCode = 'TX';
        //$rateRequest->RequestedShipment->Shipper->Address->PostalCode = 75223;
        //$rateRequest->RequestedShipment->Shipper->Address->CountryCode = 'US';

//recipient
        //$rateRequest->RequestedShipment->Recipient->Address->StreetLines = ['13450 Farmcrest Ct'];
        //$rateRequest->RequestedShipment->Recipient->Address->City = 'Herndon';
        //$rateRequest->RequestedShipment->Recipient->Address->StateOrProvinceCode = 'CA';
        //$rateRequest->RequestedShipment->Recipient->Address->PostalCode = 90262;
        //$rateRequest->RequestedShipment->Recipient->Address->CountryCode = 'US';

//shipping charges payment
        //$rateRequest->RequestedShipment->ShippingChargesPayment->PaymentType = SimpleType\Payment::_SENDER;
        $rateRequest->RequestedShipment->ShippingChargesPayment->PaymentType = PaymentType::_SENDER;
        $rateRequest->RequestedShipment->ShipTimestamp =$shipDate->format('c');
//rate request types
        $rateRequest->RequestedShipment->RateRequestTypes = [RateRequestType::_PREFERRED, RateRequestType::_LIST];
        //$rateRequest->RequestedShipment->PackagingType = PackagingType::_YOUR_PACKAGING;
        //$rateRequest->RequestedShipment->PackagingType = PackagingType::_FEDEX_PAK;
        $rateRequest->RequestedShipment->PackagingType = $packageOption;
        $rateRequest->RequestedShipment->ServiceType=$serviceType;
        if ($one_rate!='')
            $rateRequest->RequestedShipment->ServiceOptionType = 'FEDEX_ONE_RATE';
        $rateRequest->RequestedShipment->PackageCount = count($Items);

//create package line items
        $LineItems=[];
        foreach ( $package_weight as $key=>$val ) {
            $LineItems[] = new RequestedPackageLineItem();
        }
        $rateRequest->RequestedShipment->RequestedPackageLineItems = $LineItems;

        foreach ( $package_weight as $key=>$value ){
            $rateRequest->RequestedShipment->RequestedPackageLineItems[$key]->Weight->Value = $value;
            $rateRequest->RequestedShipment->RequestedPackageLineItems[$key]->Weight->Units = WeightUnits::_LB;
            $rateRequest->RequestedShipment->RequestedPackageLineItems[$key]->Dimensions->Length = 8;
            $rateRequest->RequestedShipment->RequestedPackageLineItems[$key]->Dimensions->Width = 8;
            $rateRequest->RequestedShipment->RequestedPackageLineItems[$key]->Dimensions->Height = 8;
            $rateRequest->RequestedShipment->RequestedPackageLineItems[$key]->Dimensions->Units = LinearUnits::_IN;
            $rateRequest->RequestedShipment->RequestedPackageLineItems[$key]->GroupPackageCount = 1;
        }
        //echo '<pre>';print_r($rateRequest);die;
        try {

            $rateServiceRequest = new \FedEx\RateService\Request();
            if (self::$ENV == 'PRODUCTION')
                $rateServiceRequest->getSoapClient()->__setLocation(\FedEx\RateService\Request::PRODUCTION_URL); //use production URL

                $rateReply = $rateServiceRequest->getGetRatesReply($rateRequest, true); // send true as the 2nd argument to return the SoapClient's stdClass response.
                return $rateReply;

            } catch (\SoapFault $er) {
            return $er->getMessage();
            //die();
        }

    }
    public static function createOpenShipment($CustomerInfo, $ShipperInfo, $OrderItems, $courierId, $packageWeight, $serviceType, $packageOption, $serviceTypeOption, $ship_date){

        self::SetCredentials($courierId);
        $shipDate = new \DateTime($ship_date);

        $createOpenShipmentRequest = new CreateOpenShipmentRequest();
// web authentication detail
        $createOpenShipmentRequest->WebAuthenticationDetail->UserCredential->Key = self::$FEDEX_KEY;
        $createOpenShipmentRequest->WebAuthenticationDetail->UserCredential->Password = self::$FEDEX_PASSWORD;
// client detail
        $createOpenShipmentRequest->ClientDetail->MeterNumber = self::$FEDEX_METER_NUMBER;
        $createOpenShipmentRequest->ClientDetail->AccountNumber =self::$FEDEX_ACCOUNT_NUMBER;
// version
        $createOpenShipmentRequest->Version->ServiceId = 'ship';
        $createOpenShipmentRequest->Version->Major = 15;
        $createOpenShipmentRequest->Version->Intermediate = 0;
        $createOpenShipmentRequest->Version->Minor = 0;

// package 1
        $LineItems = [];

        if( count($packageWeight) == 1 ){ // order items names , if seller sending one box with multiple item products then we will plus all sku names in single variable
            $ItemNames = '';
            foreach ($OrderItems as $val){
                $ItemNames.=$val['item_sku'].' + ';
            }

            foreach ( $packageWeight as $Key=>$Weight ){
                $requestedPackageLineItem = new \FedEx\OpenShipService\ComplexType\RequestedPackageLineItem();
                $requestedPackageLineItem->SequenceNumber = 1;
                $requestedPackageLineItem->ItemDescription = $ItemNames;
                $requestedPackageLineItem->Dimensions->Width = 8;
                $requestedPackageLineItem->Dimensions->Height = 8;
                $requestedPackageLineItem->Dimensions->Length = 8;
                $requestedPackageLineItem->Dimensions->Units = \FedEx\OpenShipService\SimpleType\LinearUnits::_IN;
                $requestedPackageLineItem->Weight->Value = $Weight;
                $requestedPackageLineItem->Weight->Units = \FedEx\OpenShipService\SimpleType\WeightUnits::_LB;

                $LineItems[$Key] = $requestedPackageLineItem;
            }
        }else if ( count($packageWeight) > 1 ){ // if seller sending multiple items in multiple boxes seperately
            foreach ( $OrderItems as $Key=>$ItemVal ){
                $requestedPackageLineItem = new \FedEx\OpenShipService\ComplexType\RequestedPackageLineItem();
                $requestedPackageLineItem->SequenceNumber = 1;
                $requestedPackageLineItem->ItemDescription = $ItemVal['item_sku'];
                $requestedPackageLineItem->Dimensions->Width = 8;
                $requestedPackageLineItem->Dimensions->Height = 8;
                $requestedPackageLineItem->Dimensions->Length = 8;
                $requestedPackageLineItem->Dimensions->Units = \FedEx\OpenShipService\SimpleType\LinearUnits::_IN;
                $requestedPackageLineItem->Weight->Value = $packageWeight[$Key];
                $requestedPackageLineItem->Weight->Units = \FedEx\OpenShipService\SimpleType\WeightUnits::_LB;

                $LineItems[$Key] = $requestedPackageLineItem;
            }
        }

// requested shipment
        $createOpenShipmentRequest->RequestedShipment->DropoffType = DropoffType::_REGULAR_PICKUP;
        $createOpenShipmentRequest->RequestedShipment->ShipTimestamp = $shipDate->format('c');
        $createOpenShipmentRequest->RequestedShipment->ServiceType = $serviceType;
        $createOpenShipmentRequest->RequestedShipment->PackagingType = $packageOption;
        $createOpenShipmentRequest->RequestedShipment->ServiceOptionType=$serviceTypeOption;
        /*$createOpenShipmentRequest->RequestedShipment->ServiceType = ServiceType::_FEDEX_2_DAY;
        $createOpenShipmentRequest->RequestedShipment->PackagingType = PackagingType::_YOUR_PACKAGING;*/
        $createOpenShipmentRequest->RequestedShipment->LabelSpecification->ImageType = ShippingDocumentImageType::_PDF;
        $createOpenShipmentRequest->RequestedShipment->LabelSpecification->LabelFormatType = LabelFormatType::_COMMON2D;
        $createOpenShipmentRequest->RequestedShipment->LabelSpecification->LabelStockType = LabelStockType::_PAPER_4X6;
        $createOpenShipmentRequest->RequestedShipment->RateRequestTypes = [\FedEx\OpenShipService\SimpleType\RateRequestType::_PREFERRED];
        $createOpenShipmentRequest->RequestedShipment->PackageCount = count($LineItems);
        $createOpenShipmentRequest->RequestedShipment->RequestedPackageLineItems = $LineItems;

// requested shipment shipper
        $createOpenShipmentRequest->RequestedShipment->Shipper->AccountNumber = self::$FEDEX_ACCOUNT_NUMBER;
        $createOpenShipmentRequest->RequestedShipment->Shipper->Address->StreetLines = [$ShipperInfo[0]['address']];
        $createOpenShipmentRequest->RequestedShipment->Shipper->Address->City = $ShipperInfo[0]['city'];
        $createOpenShipmentRequest->RequestedShipment->Shipper->Address->StateOrProvinceCode = HelpUtil::getCountryStateShortCode($ShipperInfo[0]['state']);
        $createOpenShipmentRequest->RequestedShipment->Shipper->Address->PostalCode = $ShipperInfo[0]['zipcode'];
        $createOpenShipmentRequest->RequestedShipment->Shipper->Address->CountryCode = HelpUtil::getCountryStateShortCode($ShipperInfo[0]['country']);
        $createOpenShipmentRequest->RequestedShipment->Shipper->Contact->CompanyName = 'N/A';
        $createOpenShipmentRequest->RequestedShipment->Shipper->Contact->PersonName = (isset($ShipperInfo[0]['full_name'])) ? $ShipperInfo[0]['full_name'] : $ShipperInfo[0]['name'];
        $createOpenShipmentRequest->RequestedShipment->Shipper->Contact->EMailAddress = (isset($ShipperInfo[0]['email'])) ? $ShipperInfo[0]['email'] : '';
        $createOpenShipmentRequest->RequestedShipment->Shipper->Contact->PhoneNumber = $ShipperInfo[0]['phone'];

// requested shipment recipient
        $createOpenShipmentRequest->RequestedShipment->Recipient->Address->StreetLines = [$CustomerInfo[0]['customer_address']];
        $createOpenShipmentRequest->RequestedShipment->Recipient->Address->City = /*'Cohoes'*/$CustomerInfo[0]['customer_city'];
        $createOpenShipmentRequest->RequestedShipment->Recipient->Address->StateOrProvinceCode = /*'NY'*/HelpUtil::getCountryStateShortCode($CustomerInfo[0]['customer_state']);
        $createOpenShipmentRequest->RequestedShipment->Recipient->Address->PostalCode = /*'12047'*/$CustomerInfo[0]['customer_postcode'];
        $createOpenShipmentRequest->RequestedShipment->Recipient->Address->CountryCode = /*'US'*/HelpUtil::getCountryStateShortCode($CustomerInfo[0]['customer_country']);
        $createOpenShipmentRequest->RequestedShipment->Recipient->Contact->PersonName = $CustomerInfo[0]['customer_fname'] . $CustomerInfo[0]['customer_lname'];
        $createOpenShipmentRequest->RequestedShipment->Recipient->Contact->EMailAddress = $CustomerInfo[0]['shipping_email'];
        $createOpenShipmentRequest->RequestedShipment->Recipient->Contact->PhoneNumber = $CustomerInfo[0]['customer_number'];

// shipping charges payment
        $createOpenShipmentRequest->RequestedShipment->ShippingChargesPayment->Payor->ResponsibleParty = $createOpenShipmentRequest->RequestedShipment->Shipper;
        $createOpenShipmentRequest->RequestedShipment->ShippingChargesPayment->PaymentType = \FedEx\OpenShipService\SimpleType\PaymentType::_SENDER;

// send the create open shipment request
        //$openShipServiceRequest = new \FedEx\OpenShipService\Request();
        //$createOpenShipmentReply = $openShipServiceRequest->getCreateOpenShipmentReply($createOpenShipmentRequest,true);
        try{

            $openShipServiceRequest = new \FedEx\OpenShipService\Request();
            $createOpenShipmentReply = $openShipServiceRequest->getCreateOpenShipmentReply($createOpenShipmentRequest,true);
            if (self::$ENV == 'PRODUCTION')
                $openShipServiceRequest->getSoapClient()->__setLocation(\FedEx\RateService\Request::PRODUCTION_URL);
        } catch (\SoapFault $er){
            return $er->getMessage();
        }

        if ( $createOpenShipmentReply->HighestSeverity == 'SUCCESS' || $createOpenShipmentReply->HighestSeverity=='WARNING' ){
            $TrackingNumber = $createOpenShipmentReply->Index;
            $confirmShipmentReply = self::confirmOpenShipment($courierId, $TrackingNumber);
        }else{
            $confirmShipmentReply = $createOpenShipmentReply;
        }

        return $confirmShipmentReply;
    }
    public static function confirmOpenShipment($courierId, $TrackingNumber){

        self::SetCredentials($courierId);

        $confirmOpenShipmentRequest = new ConfirmOpenShipmentRequest();
        // web authentication detail
        $confirmOpenShipmentRequest->WebAuthenticationDetail->UserCredential->Key = self::$FEDEX_KEY;
        $confirmOpenShipmentRequest->WebAuthenticationDetail->UserCredential->Password = self::$FEDEX_PASSWORD;
// client detail
        $confirmOpenShipmentRequest->ClientDetail->MeterNumber = self::$FEDEX_METER_NUMBER;
        $confirmOpenShipmentRequest->ClientDetail->AccountNumber = self::$FEDEX_ACCOUNT_NUMBER;
// version
        $confirmOpenShipmentRequest->Version->ServiceId = 'ship';
        $confirmOpenShipmentRequest->Version->Major = 15;
        $confirmOpenShipmentRequest->Version->Intermediate = 0;
        $confirmOpenShipmentRequest->Version->Minor = 0;
        $confirmOpenShipmentRequest->Index = $TrackingNumber;

        $openShipServiceRequest = new \FedEx\OpenShipService\Request();

        if (self::$ENV == 'PRODUCTION')
            $openShipServiceRequest->getSoapClient()->__setLocation(\FedEx\RateService\Request::PRODUCTION_URL);

        $confirmOpenShipmentReply = $openShipServiceRequest->getConfirmOpenShipmentReply($confirmOpenShipmentRequest,true);

        return $confirmOpenShipmentReply;

    }
    public static function TrackShipment($courierId, $TrackingNumber){

        self::SetCredentials($courierId);

        $trackRequest = new TrackRequest();

        // User Credential
        $trackRequest->WebAuthenticationDetail->UserCredential->Key = self::$FEDEX_KEY;
        $trackRequest->WebAuthenticationDetail->UserCredential->Password = self::$FEDEX_PASSWORD;

        // Client Detail
        $trackRequest->ClientDetail->AccountNumber = self::$FEDEX_ACCOUNT_NUMBER;
        $trackRequest->ClientDetail->MeterNumber = self::$FEDEX_METER_NUMBER;

        // Version
        $trackRequest->Version->ServiceId = 'trck';
        $trackRequest->Version->Major = 16;
        $trackRequest->Version->Intermediate = 0;
        $trackRequest->Version->Minor = 0;

        $trackRequest->SelectionDetails = [new TrackSelectionDetail()];

        // Track shipment 1
        $trackRequest->SelectionDetails[0]->PackageIdentifier->Value = $TrackingNumber;
        $trackRequest->SelectionDetails[0]->PackageIdentifier->Type = TrackIdentifierType::_TRACKING_NUMBER_OR_DOORTAG;

        $request = new \FedEx\TrackService\Request();
        $trackReply = $request->getTrackReply($trackRequest,true);

        return $trackReply;
    }

    public static function trackShipping()
    {
        //continue
    }
    public static function GetPackageWeight($lbs,$once){
        $final_weight = [];
        foreach ( $lbs as $key=>$lbs_weight ){
            $once_key = ($once[$key] == '') ? 0 : $once[$key];
            $once_to_lbs = (  $once_key / 16);
            $final_weight[] = $lbs_weight+$once_to_lbs;
        }
        return $final_weight;
    }
}