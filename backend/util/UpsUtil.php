<?php


namespace backend\util;


class UpsUtil
{
    private static $courier=null;
    private static $courier_config=null; //json config variables

    private function make_request($url,$header,$params)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 45);
        //curl_setopt($ch, CURLOPT_PORT, 443);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        $response = curl_exec($ch);

        // CHECK TO SEE IF WE GOT AN ERROR
        // IF SO, FORMAT IT LIKE THIS   ::28::Operation timed out afterseconds
        if ((curl_errno($ch)) && (curl_errno($ch) != 0)) {
            $response = "::".curl_errno($ch)."::".curl_error($ch);
        }

        // SEND THE RESPONSE BACK TO THE SCRIPT
        return $response;
    }

    private static function set_config($courier)
    {
        self::$courier=$courier;
        self::$courier_config=json_decode($courier->configuration);
    }

    private static function make_header($type)
    {
        if($type=='address_validation') {
            return [
                'Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept',
                'Access-Control-Allow-Methods: POST',
                'Access-Control-Allow-Origin: *',
                'Content-type: application/json'
            ];
        } elseif($type=='get_shipping_rates') {
            return [
                'AccessLicenseNumber:'.self::$courier_config->AccessLicenseNumber,
                'Password:'.self::$courier_config->Password,
                'Content-Type:application/json',
                'transId:axle' . date('His'),
                'transactionSrc:XOLT',
                'Username:'.self::$courier_config->UserId,
              //  'Accept:application/json '
            ];
        } elseif($type=='submit_shipping') {
            return [
                'AccessLicenseNumber:'.self::$courier_config->AccessLicenseNumber,
                'Password:'.self::$courier_config->Password,
                'Content-Type:application/json',
                'transId:axle' . date('His'),
                'transactionSrc:XOLT',
                'Username:'.self::$courier_config->UserId,
                'Accept:application/json '
            ];
        }

    }

    /*
     * check input validation
     */

    private static function _check_address_inputs($address)
    {
        if(!isset($address['state']) || empty($address['state']))
            return ['status'=>false,'error'=>"state field required"];

        if(!isset($address['city']) || empty($address['city']))
            return ['status'=>false,'error'=>"city field required"];

        if(!isset($address['zip']) || empty($address['zip']))
            return ['status'=>false,'error'=>"zip field required"];

        return ['status'=>true,'error'=>""];
    }

    /*
     * check if address validation is good
     */

    public static function ValidateAddress($courier=null,$address=null)
    {

        if($courier && $address):
            self::set_config($courier);
            if(!$validated=self::_check_address_inputs($address)['status']) //validation
                return ['status'=>'failure','msg'=>$validated['error']];

           // $url="https://wwwcie.ups.com/rest/AV";  //test
           // $url=self::$courier->url."/rest/AV";  //test
            $url="https://onlinetools.ups.com/rest/AV";  //production
            $headers=self::make_header('address_validation');
            $access_request=['AccessLicenseNumber'=>self::$courier_config->AccessLicenseNumber,'UserId'=>self::$courier_config->UserId,'Password'=>self::$courier_config->Password];
            $request=['TransactionReference'=>['CustomerContext'=>'Customer Data'],'RequestAction'=>'AV'];
            $address=['City'=>$address['city'],'StateProvinceCode'=>$address['state'],'PostalCode'=>$address['zip']];
           // $address=['City'=>'Austin','StateProvinceCode'=>'TX','PostalCode'=>'73301'];
            $params=['AccessRequest'=>$access_request,'AddressValidationRequest'=>['Request'=>$request,'Address'=>$address]];
            $params=json_encode($params);
            $response=self::make_request($url,$headers,$params);
            $response=json_decode($response);

            if(isset($response->AddressValidationResponse->Response->ResponseStatusCode) && $response->AddressValidationResponse->Response->ResponseStatusCode)
            {
                $address_list=$response->AddressValidationResponse->AddressValidationResult;
                $address_list=is_array($address_list) ? $address_list :(array) $address_list;

                if(isset($address_list['Rank']) && $address_list['Rank'] && isset($address_list['Quality']) && $address_list['Quality']==1)
                    return ['status'=>'success','error'=>'','msg'=>'address valid'];
                else
                {
                    $addresses=[];
                    foreach ($address_list as $k=>$val)
                    {
                        $addresses[]=['city'=>$val->Address->City,'state'=>$val->Address->StateProvinceCode,'zip'=>$val->PostalCodeLowEnd];
                    }
                    return ['status'=>'failure','error'=>'suggestion_given','msg'=>'Customer address not verified by UPS ,check suggestions below','suggestions'=>$addresses];
                }
            }
            else
            {
                return ['status'=>'failure','error'=>'failed_courier_address_validation','msg'=>'Customer address validation failed by UPS'];
            }
        endif;
        return ['status'=>'failure','error'=>'failed_courier_params','msg'=>'courier input failure'];
    }



    private  function _check_shipping_rates_address_inputs($address)
    {
        if(!isset($address['state']) || empty($address['state']))
            return ['status'=>false,'error'=>"state field required"];

        if(!isset($address['city']) || empty($address['city']))
            return ['status'=>false,'error'=>"city field required"];

        if(!isset($address['zip']) || empty($address['zip']))
            return ['status'=>false,'error'=>"zip field required"];

        if(!isset($address['country']) || empty($address['country']))
            return ['status'=>false,'error'=>"country field required"];

        return ['status'=>true,'error'=>""];
    }

    /*
    * get shipping rates based on package selected
    */

    public static function getShippingRates($courier=null,$params)
    {
        if($courier && $params):
            self::set_config($courier);
            $check_inputs=self::_check_shipping_rates_address_inputs($params['customer_address']);
            if(!$check_inputs['status'])
                return ['status'=>'failure','msg'=>$check_inputs['error']];

            $check_inputs=self::_check_shipping_rates_address_inputs($params['warehouse']);
            if(!$check_inputs['status'])
                return ['status'=>'failure','msg'=>$check_inputs['error']];
            //$customer_address=self::_format_address_state_country($params['customer_address']);
          //  $url="https://wwwcie.ups.com/ship/v1/rating/Rate";  //test
            $url="https://onlinetools.ups.com/ship/v1/rating/Rate";  //production
            $headers=self::make_header('get_shipping_rates');
            ///request
            $request=['SubVersion'=>'1703','TransactionReference'=>['CustomerContext'=>'Shipment Charges']];
            ///shipment
            $shipper=[
                    'Name'=>$params['warehouse']['name'],
                    'ShipperNumber'=>isset($params['warehouse']['shipper_number']) ? $params['warehouse']['shipper_number']:"",
                    'Address'=>[
                            'AddressLine'=>$params['warehouse']['address'],
                            'City'=>$params['warehouse']['city'],
                            'StateProvinceCode'=>HelpUtil::getCountryStateShortCode($params['warehouse']['state']),
                            'PostalCode'=>$params['warehouse']['zip'],
                            'CountryCode'=>HelpUtil::getCountryStateShortCode($params['warehouse']['country']) ]
                    ];
            $ship_to=['Name'=>$params['customer_address']['fname'],
                      //'ShipperNumber'=>'',
                      'Address'=>['AddressLine'=>$params['customer_address']['address'],
                                  'City'=>$params['customer_address']['city'],
                                  'StateProvinceCode'=>HelpUtil::getCountryStateShortCode($params['customer_address']['state']),
                                  'PostalCode'=>$params['customer_address']['zip'],
                                  'CountryCode'=>HelpUtil::getCountryStateShortCode($params['customer_address']['country'])]
                    ];
            //$ship_from=['Name'=>'Gr5 Combat LLC','ShipperNumber'=>'','Address'=>['AddressLine'=>'25825 Plymouth rd','City'=>'REDFORD','StateProvinceCode'=>'MI','PostalCode'=>'48239','CountryCode'=>'US']];
           // $ship_from=$shipper;
            $weight=['UnitOfMeasurement'=>['Code'=>'LBS','Description'=>'Pounds']]; //,'Weight'=>'10'];
            $dimensions=null;
            if($params['dimensions']['pkg_length'] && $params['dimensions']['pkg_width'] && $params['dimensions']['pkg_height'])
            {
                $dimensions=['UnitOfMeasurement'=>['Code'=>'IN'],'Length'=>$params['dimensions']['pkg_length'],'Width'=>$params['dimensions']['pkg_width'],'Height'=>$params['dimensions']['pkg_height']];
            }
            $package=[
                    'PackagingType'=>['Code'=>$params['package_type']['package_id'],'Description'=>$params['package_type']['package_name']],
                    'PackageWeight'=>['UnitOfMeasurement'=>['Code'=>'LBS'],'Weight'=>$params['dimensions']['pkg_weight'].".".$params['dimensions']['pkg_weight_oz']]
                   // 'PackageWeight'=>['UnitOfMeasurement'=>['Code'=>'LBS'],'Weight'=>'0.5']
                     ];
            if($dimensions)
                $package['Dimensions']=$dimensions;

            $shipment=[ //'ShipmentRatingOptions'=>['UserLevelDiscountIndicator'=>true],
                       // 'FRSPaymentInformation'=>['Type'=>['Code'=>'02'],'Address'=>['PostalCode'=>'48239','CountryCode'=>'US']],
                         'Shipper'=>$shipper,
                         'ShipTo'=>$ship_to,
                         'ShipFrom'=>$shipper,
                        // 'Service'=>['Code'=>'14','Description'=>'UPS Next Day Air Early'],
                         'Service'=>['Code'=>$params['service']['service_id'],'Description'=>$params['service']['service_name']],
                         'ShipmentTotalWeight'=>$weight,
                         'Package'=>$package
            ];
            ///
            $prepared['RateRequest']=['Request'=>$request,'Shipment'=>$shipment];
            $prepared=json_encode($prepared);
           // echo $prepared; die();
            $response=self::make_request($url,$headers,$prepared);
            $response=json_decode($response);
          //  echo "<pre>";
           //print_r($response); die();
            if(isset($response->RateResponse->Response))
            {
                $status=$response->RateResponse->Response->ResponseStatus->Code;
                if($status)
                {
                    $package=isset($response->RateResponse->RatedShipment->RatedPackage) ? $response->RateResponse->RatedShipment->RatedPackage:NULL;
                    $charges= ['CurrencyCode'=>$package->TotalCharges->CurrencyCode,'amount'=>$package->TotalCharges->MonetaryValue];
                    return ['status'=>'success','msg'=>'','charges'=>$charges];
                }
            }
            else
            {
                $error=isset($response->response->errors[0]->message) ? $response->response->errors[0]->message:'not found';
                return ['status'=>'failure','msg'=>$error];
            }
        endif;
        return ['status'=>'failure','msg'=>'courier input failure'];
    }

    private  function _check_shipping_address_inputs($address)
    {
        //print_r($address); die();
        if(!isset($address['state']) || empty($address['state']))
            return ['status'=>false,'error'=>"state field required"];

        if(!isset($address['address']) || empty($address['address']))
            return ['status'=>false,'error'=>"address field required"];

        if(!isset($address['city']) || empty($address['city']))
            return ['status'=>false,'error'=>"city field required"];

        if(!isset($address['zip']) || empty($address['zip']))
            return ['status'=>false,'error'=>"zip field required"];

        if(!isset($address['country']) || empty($address['country']))
            return ['status'=>false,'error'=>"country field required"];

        return ['status'=>true,'error'=>""];
    }
    /*
     * submit shipping to courier
     */

    public static function submitShipping($courier=null,$params)
    {
        if($courier):
            self::set_config($courier);
             $url=self::$courier->url."/ship/v1/shipments?additionaladdressvalidation=city";
             //$url="https://wwwcie.ups.com/ship/v1/shipments?additionaladdressvalidation=city";  //test
           // $url="https://onlinetools.ups.com/ship/v1/shipments";  //production
            $check_inputs=self::_check_shipping_address_inputs($params['customer']);  //customer inputs
            if(!$check_inputs['status'])
                return ['status'=>'failure','msg'=>$check_inputs['error']];

            $check_inputs=self::_check_shipping_address_inputs($params['shipper']);  //shipper inputs
            if(!$check_inputs['status'])
                return ['status'=>'failure','msg'=>$check_inputs['error']];

            $headers=self::make_header('submit_shipping');
            //shipper
            $shipper=['Name'=>$params['shipper']['name'],
                    'AttentionName'=>$params['shipper']['name'],
                    'ShipperNumber'=>$params['shipper']['shipper_number'],
                    //'FaxNumber'=>'1234567',
                  //  'TaxIdentificationNumber'=>'1234567',
                    'Address'=>['AddressLine'=>$params['shipper']['address'],'City'=>$params['shipper']['city'],'StateProvinceCode'=>$params['shipper']['state'],'PostalCode'=>$params['shipper']['zip'],'CountryCode'=>$params['shipper']['country']]
            ];
            if (isset($params['shipper']['phone']) && !empty($params['shipper']['phone'])) {
                $shipper['phone']=['Number'=>$params['shipper']['phone']];
            }

            //receiver // customer

            $ship_to=['Name'=>$params['customer']['name'],
                'AttentionName'=>$params['customer']['name'],
                //'FaxNumber'=>'1234560',
                //'TaxIdentificationNumber'=>'1234566',
                'Address'=>['AddressLine'=>$params['customer']['address'],'City'=>$params['customer']['city'],'StateProvinceCode'=>$params['customer']['state'],'PostalCode'=>$params['customer']['zip'],'CountryCode'=>$params['customer']['country']]
            ];
            if(isset($params['customer']['phone']) && !empty($params['customer']['phone'])) {
                $ship_to['phone']=['Number'=>$params['customer']['phone']];
            }

            $payment_information=['ShipmentCharge'=>['Type'=>'01','BillShipper'=>['AccountNumber'=>$params['shipper']['shipper_number']]]];
            $package=[
                        'Description'=>$params['package_type'],
                        'Packaging'=>['Code'=>$params['package_type']],
                        'PackageWeight'=>['UnitOfMeasurement'=>['Code'=>'LBS'],'Weight'=>$params['package']['weight'].".".$params['package']['weight_oz']],
                        'PackageServiceOptions'=>'domestic transfer',
            ];
            $shipment=[
                        'Description'=>isset($params['order_number']) ? $params['order_number']:'ezcommerce used',
                        'Shipper'=>$shipper,
                        'ShipTo'=>$ship_to,
                        'ShipFrom'=>$shipper,
                        'PaymentInformation'=>$payment_information,
                        'Service'=>['Code'=>$params['service']['code'],'Description'=>$params['service']['name']],
                        'Package'=>$package,
                        'ItemizedChargesRequestedIndicator'=>'',
                        'RatingMethodRequestedIndicator'=>'',
                        'TaxInformationIndicator'=>'',
                        'ShipmentRatingOptions'=>['NegotiatedRatesIndicator'=>''],
            ];
            $prepared['ShipmentRequest']=['Shipment'=>$shipment,'LabelSpecification'=>['LabelImageFormat'=>['Code'=>'GIF']]];
            $prepared=json_encode($prepared);
            $response=self::make_request($url,$headers,$prepared);
           // echo $response; die();
            $response=json_decode($response);
            //echo "<pre>";
            //print_r($response); die();
            if(isset($response->ShipmentResponse->Response))
            {

                if ($response->ShipmentResponse->Response->ResponseStatus->Code) {
                    $shipment_Result=isset($response->ShipmentResponse->ShipmentResults) ? $response->ShipmentResponse->ShipmentResults:"";
                    $label_image=$shipment_Result->PackageResults->ShippingLabel->GraphicImage; // encoded text
                    unset($shipment_Result->PackageResults->ShippingLabel->GraphicImage); // for full response to save db space
                    unset($shipment_Result->PackageResults->ShippingLabel->HTMLImage); //// for full response to save db space
                    $return=[
                            'status'=>'success',
                            'amount_inc_taxes'=>$shipment_Result->ShipmentCharges->TotalCharges->MonetaryValue,
                            'amount_exc_taxes'=>$shipment_Result->ShipmentCharges->TotalCharges->MonetaryValue,
                            'currency_code'=>$shipment_Result->ShipmentCharges->TotalCharges->CurrencyCode,
                            'tracking_number'=>$shipment_Result->PackageResults->TrackingNumber,
                            'additional_info'=>['shipment_id'=>$shipment_Result->ShipmentIdentificationNumber],
                            'dimensions'=>$params['package'],  // ['length','width','height','weight']
                          //  'shipping_label_gif'=>$shipment_Result->PackageResults->ShippingLabel->GraphicImage,
                          //  'shipping_label_html'=>$shipment_Result->PackageResults->ShippingLabel->HTMLImage,
                            'label'=>self::generate_label($label_image,$shipment_Result->PackageResults->TrackingNumber),
                            'full_response'=>$shipment_Result
                    ];
                    return $return;
                } else {
                    return ['status'=>'failure','msg'=>'Failed to ship'];
                }
            }
            else
            {
                return ['status'=>'failure','msg'=>isset($response->response->errors[0]->message) ? $response->response->errors[0]->message: 'shipment Failure'];
            }
        endif;
        return ['status'=>'failure','msg'=>'courier input failure'];
    }

    /*
     * generate label from string
     */
    public static  function generate_label($raw_string,$name)
    {
        if($raw_string):
            $path='shipping-labels';
            $bin = base64_decode($raw_string);
            $im= imagecreatefromstring($bin);
            $im = imagerotate($im, -90, 0);
            if (!$im)
                return null; // mean not valid image

            if(!is_dir($path)) //create the folder if it's not already exists
                mkdir($path,0755,TRUE);

            $img_file =$path . '/' . $name .".png";
            imagepng($im, $img_file, 0);
            return  $name.'.png';
        endif;
        return null;
    }

    /*
     * track shipping
     */
    public static function trackShipping($courier=null,$tracking_number=null)
    {

        if($courier && $tracking_number):
            self::set_config($courier);

            $url=self::$courier->url . "/rest/Track";
                //"https://wwwcie.ups.com/rest/Track";  //test
            //$url="https://onlinetools.ups.com/rest/AV";  //production
            $headers=self::make_header('address_validation');
            $user_token=['Username'=>self::$courier_config->UserId,'Password'=>self::$courier_config->Password];
            $track_request=['Request'=>['RequestOption'=>'1','TransactionReference'=>['CustomerContext'=>'Track Number']],'InquiryNumber'=>$tracking_number];
            $params=['UPSSecurity'=>['UsernameToken'=> $user_token,'ServiceAccessToken'=>['AccessLicenseNumber'=>self::$courier_config->AccessLicenseNumber]],'TrackRequest'=>$track_request];
            $params=json_encode($params);
           // echo $params; die();
            $response=self::make_request($url,$headers,$params);
           //echo "<pre>";
           // print_r($response); die();
            if($response)
            {
               // echo $response; die();
                $response=json_decode($response);
                if(isset($response->TrackResponse->Response->ResponseStatus->Code) && $response->TrackResponse->Response->ResponseStatus->Code==1)
                {
                    $package=isset($response->TrackResponse->Shipment->Package) ? $response->TrackResponse->Shipment->Package:NULL ;
                  //  print_r($package); die();
                    $status= self::_refine_tracking_shipping_response($package,$tracking_number);
                    return ['status'=>'success','courier_status'=>$status,'shipping_history'=>[],'msg'=>'','error'=>''];
                  //  echo gettype($package); die();
                }

            }
        endif;
        return ['status'=>'failure','error'=>'failed_courier_params','msg'=>'courier input failure'];
    }

    private static function _refine_tracking_shipping_response($package=null,$input_tracking_number)
    {
        if($package):
            $raw=[];
            if(gettype($package)!='array')
                    $raw[]=$package;
            else
                $raw=$package;  // if already array

            foreach($raw as $row)
            {
               // print_r($row); die();
                if($row->TrackingNumber==$input_tracking_number)
                {
                   // $activity=[];
                    if(isset($row->Activity))
                    {

                        /////
                        if(gettype($row->Activity)!='array')
                            $activity[]=$row->Activity;
                        else
                             $activity=$row->Activity;


                        foreach ($activity as $status)
                        {
                                if(isset($status->Status->Type) &&  strtolower($status->Status->Type)=='d' && strtolower($status->Status->Description)=='delivered')
                                        return 'delivered';
                                if(strtolower($status->Status->Description)=='canceled' || strtolower($status->Status->Description)=='cancelled')
                                       return 'canceled';
                                 if(strtolower($status->Status->Description)=='returned')
                                      return 'returned';
                        }
                    }


                }

            }
            endif;

            return null;

    }

}