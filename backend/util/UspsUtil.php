<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2/11/2020
 * Time: 11:33 AM
 */
namespace backend\util;


use yii\log\Logger;

class UspsUtil
{
        private static $courier=null;
        //private static $authenticator='exp=1583576716&uid=3110201&cty=swsim&ctk=SuppJo5Z/wXLQHSY006Dgw625dM=&iid=a9G3bE5LF0eAo1DX4ngVHQ==&rsid=0&irsid=1&eac=1534985658&eacx=297990143&eac3=0&rrsid=0&raid=0&resellerid=0&pexp=0&pl=&ast=1&ipaddr=219.92.89.20&ace=1583493916&appcapsx=W34FuhHC9/8AAAAAAAAAAA==&mac=5xa3sKGFOUFk/MX/Y2J+H1Q5TQU=';  // token
        private static $courier_config=NULL; //json config variables ,username,pass etc
        private static $services_available = [
            "US-FC"     =>  "USPS First-Class Mail",
            "US-MM"     =>  "USPS Media Mail",
            "US-PP"     =>  "USPS Parcel Post",
            "US-PM"     =>  "USPS Priority Mail",
            "US-XM"     =>  "USPS Priority Mail Express",
            "US-EMI"    =>  "USPS Priority Mail Express International",
            "US-PMI"    =>  "USPS Priority Mail International",
            "US-FCI"    =>  "USPS First Class Mail International",
            "US-CM"     =>  "USPS Critical Mail",
            "US-PS"     =>  "USPS Parcel Select",
            "US-LM"     =>  "USPS Library Mail"
        ];
    /**
     * get authentication token
     * every request to api either needs credentials(integrationid,username,pasword) or can pass authenticator token ,
     *  and every response from api gives authenticator token , which you have to pass to next api call
     * 1 token only valid for 1 call
     */

    public static function get_authenticator()
    {
        $client = new \SoapClient('https://swsim.testing.stamps.com/swsim/swsimv66.asmx?wsdl');
       // $client = new \SoapClient('https://swsim.testing.stamps.com/swsim/swsimv69.asmx?wsdl');
        $authData = [
            "Credentials"   => [
                "IntegrationID"     =>'6cb7d16b-4b4e-4717-80a3-50d7e278151d',
                "Username"          => 'GR5-001',
                "Password"          => 'January2020!'
            ]
        ];
        $result=$client->AuthenticateUser($authData);
        print_r($result); die();
        /**
         *
         */
    }

    private static function set_config($courier)
    {
        self::$courier=$courier;
        self::$courier_config=json_decode($courier->configuration);
    }

    private static function get_credentials()
    {
        return [
                "IntegrationID"     => self::$courier_config->IntegrationID ,
                "Username"          => self::$courier_config->Username ,
                "Password"          => self::$courier_config->Password ,
            ];
    }
    /*
     * address validation
     */

    public static function ValidateAddress($courier=null,$address=null)
    {
        if($courier && $address):
            self::set_config($courier);

        if(!in_array($address['country'],['US','USA']))
            return ['status'=>'failure','error'=>'Shipping only to US','msg'=>'Only USA allowed Yet'];

        $data=[
           // 'Authenticator' =>self::$authenticator, // can use either authenticator token or either credentials
            'Credentials'   =>self::get_credentials(),
            'Address' => [
                'FullName'  => $address['fname'] . " " .  $address['lname'] ,
                'Address1'  => $address['address'],
               // 'Address2'  => '',
                'City'      => $address['city'],
                'State'     => $address['state'],
                'ZIPcode'   => $address['zip'],
            ]
        ];
            try {
                    $client = new \SoapClient(self::$courier->url);
                    //$client = new \SoapClient('https://swsim.testing.stamps.com/swsim/swsimv69.asmx?wsdl');
                    $result=$client->CleanseAddress($data);
                   // print_r($result); die();
                    if(isset($result->CityStateZipOK) && $result->CityStateZipOK=="1")
                        return ['status'=>'success','error'=>'','info'=>isset($result->AddressCleansingResult) ? $result->AddressCleansingResult:"",'msg'=>'address valid'];
                    elseif(isset($result->AddressCleansingResult))
                        return ['status'=>'failure','error'=>$result->AddressCleansingResult,'msg'=>$result->AddressCleansingResult];

            } catch(\Exception $e) {
                return ['status'=>'failure','error'=>$e->getMessage(),'msg'=>$e->getMessage()];
            }
        endif;
        return ['status'=>'failure','error'=>'failed_courier_params','msg'=>'courier input failure'];

      //  $cleanseToAddressResponse = $this->makeCall('CleanseAddress', $d);
    }

    /*
    * get shipping rates based on package selected
    */

    public static function getShippingRates($courier=null,$zip_codes,$dimensions,$package,$ship_date)
    {
        self::set_config($courier);
        $services=[]; // return variable
        $rate=[
            "FromZIPCode"   => $zip_codes['from'],//48239,
            "ToZIPCode"   => $zip_codes['to'],
            "PackageType"   => $package['package_id'],
            "ShipDate"  =>$ship_date, //date('Y-m-d'),
            "InsuredValue"  => 100
        ];

        if($dimensions['pkg_weight'])
            $rate['WeightLb']=$dimensions['pkg_weight'];
        if($dimensions['pkg_weight_oz'])
            $rate['WeightOz']=$dimensions['pkg_weight_oz'];
        if($dimensions['pkg_length'])
            $rate['Length']=$dimensions['pkg_length'];
         if($dimensions['pkg_width'])
             $rate['Width']=$dimensions['pkg_width'];
        if($dimensions['pkg_height'])
            $rate['Height']=$dimensions['pkg_height'];

        $data = [
          // "Authenticator" => self::$authenticator,
            "Credentials"   => self::get_credentials(),
            "Rate" =>$rate

        ];
        //echo "<pre>";
      //  print_r($data); die();
        //$client = new \SoapClient('https://swsim.testing.stamps.com/swsim/swsimv69.asmx?wsdl');
        try {
            $client = new \SoapClient(self::$courier->url,array(
                'exceptions' => true,
            ));
            $result=$client->getRates($data);
          //  echo "<pre>";

           // print_r($result); die();
            if(isset($result->Rates->Rate)){
                if(!is_array($result->Rates->Rate))
                   $rates_obj[]= $result->Rates->Rate;
                else
                    $rates_obj= $result->Rates->Rate;
              //  print_r($rates_obj); die();

                foreach($rates_obj as $service) { // get all services , its addons and amount

                    ///if addons
                     $addons=[];
                    foreach($service->AddOns->AddOnV16 as $addon){
                        $addons[]=[
                                'code'=>$addon->AddOnType,
                                'amount'=>isset($addon->Amount) ? $addon->Amount:0 ,

                        ];
                    }
                    ///
                    $services[$service->ServiceType]=[
                        'code'=>$service->ServiceType,
                        'name'=>self::$services_available[$service->ServiceType], // get name against code
                        'amount'=>$service->Amount,
                        'delivery_days'=>$service->DeliverDays,
                        'addons'=>$addons,
                    ];
                }
            }
        }catch(\Exception $e) {
            return ['status'=>'failure','error'=>$e->getMessage(),'msg'=>$e->getMessage()];
        }

        if($services)
            return ['status'=>'success','error'=>'','services'=>$services,'msg'=>'services found'];
        else
            return ['status'=>'failure','error'=>'failed to get services','msg'=>'failed to get services'];

    }

    /*
    * submit shipping to courier
    */

    public static function submitShipping($courier=null,$params=null)
    {
       if($courier && $params):
        self::set_config($courier);
        //////////////
         $rateOptions = [
                    "FromZIPCode"   =>$params['shipper']['zip'],
                    "ToZIPCode"    =>$params['customer']['zip'],
                    'WeightLb'     => $params['package']['weight'],
                    'ServiceType'  => $params['service']['code'] , //'US-MM',//US-FC',
                    "PackageType"   => $params['package_type'] ,//"Package",
                    "ShipDate"  => $params['shipping_date'] ,//date('Y-m-d'),
                    "InsuredValue"  => '100',
                    'Amount' => $params['service']['amount'] ,//'7.600',
                ];

           if($params['package']['weight_oz'])
               $rateOptions['WeightOz']=$params['package']['weight_oz'];

         if(isset($params['package']['length']) && !empty($params['package']['length']))
             $rateOptions['Length']=$params['package']['length'];

         if(isset($params['package']['width']) && !empty($params['package']['width']))
            $rateOptions['Width']=$params['package']['width'];

         if(isset($params['package']['height']) && !empty($params['package']['height']))
            $rateOptions['Height']=$params['package']['height'];

         $addons_sum_amount=0; // sum of addons
         if(isset($params['addons']) && !empty($params['addons']))
         {
             $addons_sum_amount=array_sum(array_column($params['addons'],'Amount'));
             $rateOptions['AddOns']=$params['addons'];
         }


        $data= [
           // 'Authenticator'     =>self::$authenticator,
            "Credentials"   => self::get_credentials(),
            'IntegratorTxID'    => $params['order_number'].$params['channel_id'] ."EZ",
            'SampleOnly'        =>false, // true for testing and false for production
            'ImageType'         => 'Pdf', // default is png
            'TrackingNumber' => '',
            'Rate'              => $rateOptions,

            'From' => [
                'FullName'  =>$params['shipper']['name'],
                'Address1'  => $params['shipper']['address'],
                //'Address2'  => '',
                'City'      => $params['shipper']['city'],
                'State'     => $params['shipper']['state'],
                'ZIPCode'   => $params['shipper']['zip']
            ],

            'To' => [
                'FullName'  => $params['customer']['name'],
                'Address1'  => $params['customer']['address'],
                //'Address2'  => '',
                'City'      => $params['customer']['city'],
                'State'     => $params['customer']['state'],
                'ZIPCode'   => $params['customer']['zip']
            ]
        ];
        // echo "<pre>";
       // print_r($data); die();
        try {
            $client = new \SoapClient(self::$courier->url,array(
                'exceptions' => true, // handle exceptions  not mendatory
            ));
            $result=$client->CreateIndicium($data);
          //echo "<pre>";
          //  print_r($result); die();
            if(isset($result->TrackingNumber))
            {
                $return=[
                    'status'=>'success',
                    'amount_inc_taxes'=>$result->Rate->Amount,
                    'amount_exc_taxes'=>$result->Rate->Amount,
                    'extra_charges'=>$addons_sum_amount,
                    //'shipment_id'=>$result->IntegratorTxID,
                    'tracking_number'=>$result->TrackingNumber,
                    'shipping_date'=>$params['shipping_date'],
                   // 'weight'=>$params['package']['weight'],
                    'additional_info'=>['IntegratorTxID'=>$result->IntegratorTxID,'StampsTxID'=>$result->StampsTxID],
                    'dimensions'=>$params['package'],  // ['length','width','height','weight']
                    'label'=>self::save_pdf($result->URL,$result->TrackingNumber),
                    'full_response'=>$result
                ];
                return $return;
            }

        } catch(\Exception $e) {
            return ['status'=>'failure','error'=>$e->getMessage(),'msg'=>$e->getMessage()];
        }
        endif;
        return ['status'=>'failure','error'=>'failed to get services','msg'=>'failed to get services'];
    }

    /*
    * save pdf from url
    */

    private function save_pdf($url,$name)
    {
        $path = "shipping-labels";
        if(!is_dir($path)) //create the folder if it's not already exists
            mkdir($path,0755,TRUE);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($ch, CURLOPT_REFERER, 'https://www.sedi.ca/sedi/SVTReportsAccessController?menukey=15.03.00&locale=en_CA');
        $data = curl_exec($ch);
        curl_close($ch);
        $result = file_put_contents($path."/".$name.".pdf", $data);
        if($result)
            return $name.".pdf";
        else
            return NULL;
    }
    /*
     * track shipping
     */
    public static function trackShipping($courier=null,$tracking_number=null)
    {
        if($courier && $tracking_number):
            self::set_config($courier);

            $data= [
                //'Authenticator'     =>self::$authenticator,
                "Credentials"   => self::get_credentials(),
                'TrackingNumber' => $tracking_number
                //'StampsTxID' => 'deba02e6-9128-4a61-b7d4-24cbcfbef5d3'
            ];
            try {
                $client = new \SoapClient(self::$courier->url,array(
                    'exceptions' => true, // handle exceptions  not mendatory
                ));
                $result=$client->TrackShipment($data);
                if(isset($result->TrackingEvents->TrackingEvent))
                {
                    if(!is_array($result->TrackingEvents->TrackingEvent))
                        $status_obj[]= $result->TrackingEvents->TrackingEvent;
                    else
                        $status_obj= $result->TrackingEvents->TrackingEvent;

                    $status_history=[];
                    foreach($status_obj as $status) { // get all events or statuses
                    $status_history[]=[
                                    'date'=>$status->Timestamp,
                                    'status'=>$status->Event,
                                    'adress'=>[
                                            'city'=>$status->City,
                                            'state'=>$status->State,
                                            'zip'=>$status->Zip,
                                            'country'=>$status->Country,
                                    ],
                    ];
                  }

                    return ['status'=>'success',
                            'courier_status'=>isset($status_history[0]['status']) ? $status_history[0]['status']:NULL, // current status
                            'expected_delivery_date'=>isset($result->ExpectedDeliveryDate) ? $result->ExpectedDeliveryDate:NULL,
                            'shipping_history'=>$status_history,
                            'msg'=>'',
                            'error'=>''];
                }

            } catch(\Exception $e) {
                return ['status'=>'failure','error'=>$e->getMessage(),'msg'=>$e->getMessage()];
            }
        endif;
        return ['status'=>'failure','error'=>'failed to process','msg'=>'failed to process'];
    }

    public static function cancelShipment($courier=null,$stamp_trxid=null)
    {
      
        if($courier && $stamp_trxid):
            self::set_config($courier);

            $data= [
                //'Authenticator'     =>self::$authenticator,
                "Credentials"   => self::get_credentials(),
                'StampsTxID' => $stamp_trxid
                //'StampsTxID' => 'deba02e6-9128-4a61-b7d4-24cbcfbef5d3'
            ];try {
            $client = new \SoapClient(self::$courier->url,array(
                'exceptions' => true, // handle exceptions  not mendatory
            ));
            $result=$client->CancelIndicium($data);
            if(isset($result->Authenticator))
                return ['status'=>'success','error'=>'','msg'=>'updated'];

        } catch(\Exception $e) {
            return ['status'=>'failure','error'=>$e->getMessage(),'msg'=>$e->getMessage()];
        }
        endif;
        return ['status'=>'failure','error'=>'failed to update','msg'=>'failed to update'];
    }
}