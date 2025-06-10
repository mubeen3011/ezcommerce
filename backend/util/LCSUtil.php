<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2/11/2020
 * Time: 11:33 AM
 */
namespace backend\util;
use common\models\Settings;
use Developifynet\LeopardsCOD\LeopardsCODClient;

class LCSUtil
{
    private static $courier=null;
    private static $courier_config=null;
    private static $zone_A_cities=['lahore', 'islamabad', 'rawalpindi', 'multan', 'hyderabad', 'sahiwal', 'sukkur','sukhur', 'faisalabad',
        'rahim yar khan','rahim yar kha','rahimyarkhan', 'bahawalpur', 'gujranwala', 'gujrat', 'peshawar'];

    private static function set_config($courier)
    {
        self::$courier=$courier;
        self::$courier_config=json_decode($courier->configuration);
        return;
    }

    public static function makePostApiCall( $url,$data,$header){
        $curl_handle = curl_init();
        curl_setopt($curl_handle, CURLOPT_URL, $url);  // Write here Test or Production Link
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_handle, CURLOPT_POST, 1);

        curl_setopt($curl_handle, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $data);

        $response= curl_exec($curl_handle);
        $err = curl_error($curl_handle);
        curl_close($curl_handle);
        if ($err){
            return json_encode($err);
        }
        return $response;
    }
    public static function ValidateAddress($courier,$address)
    {
        if($courier)
            self::set_config($courier);

        if(!isset($address['city']))
            return ['status'=>'failure','error'=>'City required','msg'=>'Customer City required'];
        if(!isset($address['address']))
            return ['status'=>'failure','error'=>'Address required','msg'=>'Customer Address required'];

        $customer_city=strtolower($address['city']);
        $cities= self::getCities();
        if($cities){
            $list=array_column($cities,'name');
            $city_ids=array_column($cities,'id');
            $list = array_map('strtolower', $list); // convert all values to lower
            if(in_array($customer_city,$list) || in_array($customer_city,$city_ids)){
                return ['status'=>'success','msg'=>'validated','error'=>''];
            }else
                return ['status'=>'failure','msg'=>'Customer City ' . $address['city'] .' could not match , try assign from dropdown','error'=>'city mismatched'];
        }
    }

    private static function getCities()
    {
        $cities=Settings::findone(['name'=>'LCS_cities']); // check if cities are present in setttings table
        if($cities)
            return json_decode($cities->value);
        else{ // fetch and store in settings table
            $leopards = new LeopardsCODClient();
            $cities = $leopards->getAllCities(array(
                'api_key' => self::$courier_config->api_key,              // API Key provided by LCS
                'api_password' => self::$courier_config->api_password,    // API Password provided by LCS
                'enable_test_mode' => false,                 // [Optional] default value is 'false', true|false to set mode test or live
            ));

            if(isset($cities['city_list']) && $cities['city_list'])
            {
                $store= new Settings();
                $store->name="LCS_cities";
                $store->value=json_encode($cities['city_list']);
                $store->save();
                return $cities['city_list'];
            }
        }
        return false;
    }

    private static function get_city_name_through_id($city_id)
    {
        $name=$city_id;
        $cities=Settings::findone(['name'=>'LCS_cities']); // check if cities are present in setttings table
        if($cities){
            $city=json_decode($cities->value);
            $city_ids=array_column($city,'id');
            $index=array_search($city_id,$city_ids);
            $name=isset($city[$index]->name) ? $city[$index]->name:$city_id ;
        }
      return $name;
    }

    private static function get_city_id_through_name($city_name)
    {
        $city_name=strtolower($city_name);
        $cities=Settings::findone(['name'=>'LCS_cities']); // check if cities are present in setttings table
        if($cities){
            $city=json_decode($cities->value);
            $city_names=array_column($city,'name');
            $city_names = array_map('strtolower', $city_names); // convert all values to lower
            $index=array_search($city_name,$city_names);
            if ($index !== FALSE )
                return $city[$index]->id;

        }
        return false;
    }

    public static function getShippingRates($courier=null,$params)
    {
        $customer_city=$params['customer_address']['city'];
        if(filter_var($customer_city, FILTER_VALIDATE_INT)){
            $customer_city=  self::get_city_name_through_id($customer_city);  // if id of city sent it will return name
        }
        $pkg_weight=$params['dimensions']['pkg_weight'];
        if($params['order_total'] >= 5000)  // if order total is greater than 5000 then no charges
        {
            return ['status'=>'success','msg'=>'','charges'=>0.00];
        }

        /*************if shipping with in same city*********/
        if(strtolower($customer_city)==strtolower($params['warehouse']['city'])) // if shipping with in same city
        {
            if($pkg_weight <=500) // if pkg weight upto 500 grams  // fix 120 charges
                 return ['status'=>'success','msg'=>'','charges'=>120];
            elseif($pkg_weight > 500 && $pkg_weight <=1000)  // upto 1kg charges 150
                return ['status'=>'success','msg'=>'','charges'=>150];

            elseif($pkg_weight > 1000 ){   /// if weight is more than 1 kg then addtional 100 rs charges per kg
                $overweight=($pkg_weight-1000); // first 1 kg basic charges so decrease that weight from total weight
                $basic_charges=150;
                if($overweight >=1000)
                    $additional_weight=ceil($overweight/1000);
                else
                    $additional_weight=1;

                $charges=(($additional_weight *100) + $basic_charges);

                return ['status'=>'success','msg'=>'','charges'=>$charges];
            }
        }
        /*************if shipping  to zone A cities*********/
        elseif(in_array(strtolower($customer_city),self::$zone_A_cities))
        {
            if($pkg_weight <=500) // if pkg weight upto 500 grams  // fix 140 charges
                return ['status'=>'success','msg'=>'','charges'=>140];
            elseif($pkg_weight > 500 && $pkg_weight <=1000)  // upto 1kg charges 175
                return ['status'=>'success','msg'=>'','charges'=>175];

            elseif($pkg_weight > 1000 ){   /// if weight is more than 1 kg then addtional 120 rs charges per kg
                $overweight=($pkg_weight-1000); // first 1 kg basic charges so decrease that weight from total weight
                $basic_charges=175;
                if($overweight >=1000)
                    $additional_weight=ceil($overweight/1000);
                else
                    $additional_weight=1;

                $charges=(($additional_weight *120) + $basic_charges);

                return ['status'=>'success','msg'=>'','charges'=>$charges];
            }
        }
        /***********************if other city  locations**************/
        else ///
        {
            if($pkg_weight <=500) // if pkg weight upto 500 grams  // fix 140 charges
                return ['status'=>'success','msg'=>'','charges'=>140];
            elseif($pkg_weight > 500 && $pkg_weight <=1000)  // upto 1kg charges 180
                return ['status'=>'success','msg'=>'','charges'=>180];

            elseif($pkg_weight > 1000 ){   /// if weight is more than 1 kg then addtional 130 rs charges per kg
                $overweight=($pkg_weight-1000); // first 1 kg basic charges so decrease that weight from total weight
                $basic_charges=180;
                if($overweight >=1000)
                    $additional_weight=ceil($overweight/1000);
                else
                    $additional_weight=1;

                $charges=(($additional_weight *130) + $basic_charges);

                return ['status'=>'success','msg'=>'','charges'=>$charges];
            }
        }
        return ['status'=>'success','msg'=>'','charges'=>200];
    }

    public static function submitShipping($courier=null,$params=null)
    {
        $validation=self::check_shipping_params($params);
        if($validation['status']=='failure')
            return $validation;

        self::set_config($courier);
        $leopards = new LeopardsCODClient();
        $to_send=[
            'api_key' => self::$courier_config->api_key,              // API Key provided by LCS
            'api_password' => self::$courier_config->api_password,    // API Password provided by LCS
            'enable_test_mode' =>false,                 // [Optional] default value is 'false', true|false to set mode test or live
            'booked_packet_weight' => $params['package']['weight'],
            // 'booked_packet_vol_weight_w' => $params['package']['width'],
            //  'booked_packet_vol_weight_h' => $params['package']['height'],
            //  'booked_packet_vol_weight_l' => $params['package']['length'],
            'booked_packet_no_piece' => '1',
            'booked_packet_collect_amount' => $params['amount_to_collect'] ? $params['amount_to_collect']:0,
            'booked_packet_order_id' => $params['order_number'],
            'origin_city' => $params['shipper']['city'],                  /** Params: 'self' or 'integer_value' e.g. 'origin_city' => 'self' or 'origin_city' => 789 (where 789 is Lahore ID)
             * If 'self' is used then Your City ID will be used.
             * 'integer_value' provide integer value (for integer values read 'Get All Cities' api documentation)
             */

            'destination_city' => $params['customer']['city'],             /** Params: 'self' or 'integer_value' e.g. 'destination_city' => 'self' or 'destination_city' => 789 (where 789 is Lahore ID)
             * If 'self' is used then Your City ID will be used.
             * 'integer_value' provide integer value (for integer values read 'Get All Cities' api documentation)
             */
            // Shipper Information
            'shipment_name_eng' => 'self',
            'shipment_email' => 'self',
            'shipment_phone' => $params['shipper']['phone'],
            'shipment_address' => $params['shipper']['address'],
            // Consingee Information
            'consignment_name_eng' => $params['customer']['name'],
           // 'consignment_email' => (isset($params['customer']['email']) && $params['customer']['email']) ? $params['customer']['email']:'',
            'consignment_phone' => $params['customer']['phone'],
            'consignment_address' => $params['customer']['address'],
           // 'special_instructions' => $params['instructions'] ? $params['instructions']: 'n/a',
        ];
        if(isset($params['customer']['email']) && $params['customer']['email'])
            $to_send['consignment_email']=$params['customer']['email'];

        if(isset($params['instructions']) && $params['instructions'])
            $to_send['special_instructions']=$params['instructions'];


        if($params['package']['width'] && $params['package']['height'] && $params['package']['length']){
            $to_send['booked_packet_vol_weight_w']=$params['package']['width'];
            $to_send['booked_packet_vol_weight_h']=$params['package']['height'];
            $to_send['booked_packet_vol_weight_l']=$params['package']['length'];
        }
        $response = $leopards->bookPacket($to_send);
        if(isset($response['track_number'])){

            return [
                'status'=>'success',
                'amount_inc_taxes'=>$params['amount_to_collect'],
                'amount_exc_taxes'=>$params['amount_to_collect'],
                'tracking_number'=>$response['track_number'],
                'additional_info'=>'shipping charges included all cash have to take from customer',
                'dimensions'=>$params['package'],  // ['length','width','height','weight']
                'label'=>self::save_pdf($response['slip_link'],$response['track_number']),
                'full_response'=>$response
            ];

        } elseif(isset($response['error_msg'])){
            return ['status'=>'failure','error'=>$response['error_msg'],'msg'=>$response['error_msg']];
        }
        return ['status'=>'failure','error'=>'failed to get services','msg'=>'failed to get services'];

    }

    /***
     * check params before shipping submit
     */
    private static function check_shipping_params($params)
    {
        if($params['package']['weight']<=0)
            return ['status'=>'failure','msg'=>'Weight should be greater than 0'];

       /* if($params['package']['length']<=0)
            return ['status'=>'failure','msg'=>'Dimensions required'];

        if($params['package']['width']<=0)
            return ['status'=>'failure','msg'=>'Dimensions required'];

        if($params['package']['height']<=0)
            return ['status'=>'failure','msg'=>'Dimensions required'];*/

        if(empty($params['customer']['city']))
            return ['status'=>'failure','msg'=>'Customer city required'];

        if(empty($params['customer']['address']))
            return ['status'=>'failure','msg'=>'Customer Address required'];

        if(empty($params['shipper']['city']))
            return ['status'=>'failure','msg'=>'Shipper city required'];

        if(empty($params['shipper']['address']))
            return ['status'=>'failure','msg'=>'Shipper Address required'];

        return ['status'=>'success','msg'=>'validated'];

    }



    public static function generate_load_sheet($courier,$tracking_nos)
    {
       // return ['status'=>'success','msg'=>'load sheet generated','sheet_id'=>'951925'];
        if(!$tracking_nos && !$courier){
            return ['status'=>'failure','msg'=>'Failed to generate'];
        }

        self::set_config($courier);
        $url=self::$courier->url."/generateLoadSheet/format/json/";
        //array('KI759347991','KI759479409','KI759428568','KI756749007'),
       $body=[
            'api_key' => self::$courier_config->api_key,
            'api_password'  =>  self::$courier_config->api_password,
            'cn_numbers'    => $tracking_nos,                      // E.g. array('XXYYYYYYYY') OR  array('XXYYYYYYY1', 'XXYYYYYYY2', 'XXYYYYYYY3') 10 Digits each number
            'courier_name'  => 'ABC',
            'courier_code'  => '123'
        ];
        $header = ['Content-Type:application/json'];
        $response=self::makePostApiCall($url,json_encode($body),$header);
        if($response){
            $result=json_decode($response);
            if(isset($result->load_sheet_id))
                return ['status'=>'success','msg'=>'load sheet generated','sheet_id'=>$result->load_sheet_id];
        }
        return ['status'=>'failure','msg'=>isset($result->error) ? $result->error:"",'error'=>isset($result->error) ? $result->error:"",'sheet_id'=>''];
    }


    public static function download_load_sheet($courier,$sheet_id)
    {
        if(!$courier){
            return ['status'=>'failure','msg'=>'Failed to generate'];
        }

        self::set_config($courier);
        $url=self::$courier->url."/downloadLoadSheet/";
        $body=[
            'api_key'       => self::$courier_config->api_key,
            'api_password'  => self::$courier_config->api_password,
            'load_sheet_id' => $sheet_id,                          // E.g. 123456
            'response_type' => "pdf"
        ];
        $header = ['Content-Type:application/json'];
        $response=self::makePostApiCall($url,json_encode($body),$header);
        $json_res=json_decode($response);
        if(json_last_error() == JSON_ERROR_NONE){
            if(isset($json_res->error))
                return ['status'=>'failure','msg'=>$json_res->error];
        }

        if($response){
            $pdf=self::save_load_sheet($response,$sheet_id);
            if($pdf)
                return ['status'=>'success','name'=>$pdf];
        }
        return ['status'=>'failure','msg'=>'Failed to generate'];

    }

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

    private static function save_load_sheet($data,$name)
    {
        $path = "order_load_sheets";
        if(!is_dir($path)) //create the folder if it's not already exists
            mkdir($path,0755,TRUE);
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
        $leopards = new LeopardsCODClient();
        try {
            $response = $leopards->trackPacket(array(
                'api_key' => self::$courier_config->api_key,              // API Key provided by LCS
                'api_password' => self::$courier_config->api_password,    // API Password provided by LCS
                'enable_test_mode' => false,                 // [Optional] default value is 'false', true|false to set mode test or live
                // 'track_numbers' => '759501552',            // E.g. 'XXYYYYYYYY' OR 'XXYYYYYYYY,XXYYYYYYYY,XXYYYYYY' 10 Digits each number
                //'track_numbers' => '759518773',            // E.g. 'XXYYYYYYYY' OR 'XXYYYYYYYY,XXYYYYYYYY,XXYYYYYY' 10 Digits each number
                'track_numbers' => $tracking_number,            // E.g. 'XXYYYYYYYY' OR 'XXYYYYYYYY,XXYYYYYYYY,XXYYYYYY' 10 Digits each number

            ));
            if (isset($response['packet_list'])) {
                return ['status' => 'success',
                    'courier_status' => isset($response['packet_list'][0]['booked_packet_status']) ? $response['packet_list'][0]['booked_packet_status'] : NULL, // current status
                    'expected_delivery_date' => NULL,
                    'shipping_history' => isset($response['packet_list'][0]['Tracking Detail']) ? $response['packet_list'][0]['Tracking Detail'] : NULL,
                    'msg' => '',
                    'error' => ''];
            }
        }catch(\Exception $e) {
            return ['status'=>'failure','error'=>$e->getMessage(),'msg'=>$e->getMessage()];
        }
        endif;
        return ['status'=>'failure','error'=>'failed to process','msg'=>'failed to process'];
    }

    /****
     * helper function for bulk shipment customer format
     */
    public static function bulk_shipping_customer_address_validation($address)
    {
        if(!isset($address['customer_city']))
            return ['status'=>'failure','error'=>'Customer City required','msg'=>'Customer City required'];
        if(!isset($address['customer_address']))
            return ['status'=>'failure','error'=>'Customer Address required','msg'=>'Customer Address required'];

        $city_id=self::get_city_id_through_name(trim($address['customer_city']));  // lcs get id of that city
        if($city_id==false)
            return ['status'=>'failure','error'=>'City not found in lCS list','msg'=>'City not found in lCS list'];

        $customer = [
            'name' => $address['customer_fname'] . " " . $address['customer_lname'] ,
            'address' =>$address['customer_address'],
            'phone' => $address['customer_number'],
            'state' =>$address['customer_state'],
            'city' => $city_id,
            'zip' => $address['customer_postcode'],
            'country' => $address['customer_country'],
        ];
        if(isset($address['shipping_email']) && $address['shipping_email'])
            $customer['email']=$address['shipping_email'];
        elseif(isset($address['billing_email']) && $address['billing_email'])
            $customer['email']=$address['billing_email'];
        else
            $customer['email']="";

        return $customer;
    }
    /****
     * helper function for bulk shipment shipper format
     */
    public static function bulk_shipping_shipper_address_validation($warehouse)
    {
        if(!isset($warehouse->city))
            return ['status'=>'failure','error'=>'Shipper City required','msg'=>'Shipper City required'];
        if(!isset($warehouse->address))
            return ['status'=>'failure','error'=>'Shipper Address required','msg'=>'Shipper Address required'];

        $city_id=self::get_city_id_through_name($warehouse->city);  // lcs get id of that city
        if($city_id==false)
            return ['status'=>'failure','error'=>'shipper City not found in lCS list','msg'=>'shipper City not found in lCS list'];

        $shipper = [
            'name' =>$warehouse->display_name,
            'shipper_number' => "",
            'phone' => $warehouse->phone,
            'address' => $warehouse->address,
            'full_address' => $warehouse->full_address,
            'state' =>$warehouse->state,
            'city' => $city_id,
            'zip' => $warehouse->zipcode,
            'country' => $warehouse->country,
        ];
        return $shipper;
    }
}