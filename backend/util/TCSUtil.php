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

class TCSUtil
{   private  static $success = "SUCCESS";
    private static $courier=null;
    private static $courier_config=null;
    private static $api_url = null;

    private static function debug($data)
    {
        echo "<pre>";
        echo print_r($data);
        die();
    }

    private static function set_config($courier)
    {

        self::$courier = $courier;
        if(self::$courier->mode == 'production'){
            self::$courier_config = json_decode($courier->configuration);
            self::$api_url = $courier->url;
       }else{
            self::$courier_config = json_decode($courier->configuration_test);
            self::$api_url = self::$courier_config->api_url_test;
       }
        return self::$api_url;

    }

    private static function makeGetApiCall($requestUrl,$headers)
    {
        $ch = curl_init($requestUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET"); //bk
        $result = curl_exec($ch);
        return $result;  //json format
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
            $list=array_column($cities,'cityName');
            $city_ids=array_column($cities,'cityID');
            $list = array_map('strtolower', $list); // convert all values to lower
            if(in_array($customer_city,$list) || in_array($customer_city,$city_ids)){
                return ['status'=>'success','msg'=>'validated','error'=>''];
            }else
                return ['status'=>'failure','msg'=>'Customer City ' . $address['city'] .' could not match , try assign from dropdown','error'=>'city mismatched'];
        }
    }

    private static function getCities()
    {
        $cities=Settings::findone(['name'=>'tcs_cities']); // check if cities are present in setttings table
        if($cities) {
            return json_decode($cities->value);
        }
        else{ // fetch and store in settings table
        $headers = array('X-IBM-Client-Id: ' . self::$courier_config->client_id, 'accept:application/json');

            $request_url= rtrim(self::$api_url, "/")."/v1/cod/cities";
            $cities_json=  self::makeGetApiCall($request_url, $headers);
            if($cities_json)
                $cities=json_decode($cities_json);

            if(isset($cities->allCities) && $cities->allCities)
            {
                $store= new Settings();
                $store->name="tcs_cities";
                $store->value=json_encode($cities->allCities);
                $store->save();
                return $cities->allCities;
            }
        }
        return false;
    }


    public static function submitShipping($courier=null,$params=null)
    {
        self::set_config($courier);
        $validation=self::check_shipping_params($params);
        if($validation['status']=='failure')
            return $validation;

        $shipper_city = self::get_city_code_through_name($params['shipper']['city']);
        $customer_city = self::get_city_code_through_name($params['customer']['city']);

        if(!$shipper_city)
            return ['status'=>'failure','msg'=>'shipper City not found in TCS cities list'];

      //  self::debug($params);
        $to_send=[
            'userName'=> self::$courier_config->{'User ID'},
            'password'=> self::$courier_config->Password,
            'costCenterCode'=> self::$courier_config->costCenterCode,
            'consigneeName'=> $params['customer']['name'],
            'consigneeName'=> (isset($params['customer']['name']) &&  $params['customer']['name']) ? $params['customer']['name']:"Sir/Madam",

            'consigneeEmail'=> (isset($params['customer']['email']) && $params['customer']['email']) ? $params['customer']['email']: 'abc@gmail.com',
            'consigneeMobNo'=> $params['customer']['phone'],
            'consigneeAddress'=> $params['customer']['address'],
            'originCityName'=> $shipper_city,
            'destinationCityName'=> $customer_city,
            'weight'=> $params['package']['weight'],
            'pieces'=> '1',
            'codAmount'=> $params['order_total'] ? $params['order_total']:0,
            'customerReferenceNo'=> $params['order']->order_number,
            'services'=> trim($params['service']['code']),
            'fragile'=> $params['fragile'],
            'remarks'=> $params['instructions'],
            'insuranceValue' => $params['insurance'],
        ];

        ////items
        $item_names = "";
        if(count($params['order_items']) >= 5)
            $item_names= count($params['order_items'])." Items";
        else{
            foreach($params['order_items'] as $item)
            {
                $item_name=\common\models\Products::find()->select('name')->where(['sku'=>$item['item_sku']])->scalar();
                $item_names .= $item_name. ", ";
            }
        }

        $to_send['productDetails']=$item_names;
        $body_enclosed = json_encode($to_send);
        $header = array('X-IBM-Client-Id: ' . self::$courier_config->client_id, "content-type: application/json", 'accept:application/json');
        $request_url=rtrim(self::$api_url, "/")."/v1/cod/create-order";
        $response =  self::makePostApiCall($request_url,$body_enclosed,$header,"POST");
        $response = json_decode($response);

       // self::debug($response);
        if(isset($response->bookingReply)){
            $cn_number = $response->bookingReply->result;
            $cn_arr = explode(':', $cn_number);
            $cn =isset($cn_arr[1]) ?  trim($cn_arr[1]):'x';

            return [
                'status'=>'success',
                'amount_inc_taxes'=>$params['order_total'],
                'amount_exc_taxes'=>$params['order_total'],
                'tracking_number'=>$cn,
                'additional_info'=>['order_code'=>$params['order']->order_number],
                'dimensions'=>isset($params['package']) ? $params['package']:NULL,  // ['length','width','height','weight']
                'label'=> 'https://envio.tcscourier.com/BookingReportPDF/GenerateLabels?consingmentNumber='.$cn, // TCS not giving label
                'full_response'=>$response
            ];
        } else if(isset($response->Error_message)){
            return ['status'=>'failure','error'=>$response->Error_message,'msg'=>$response->Error_message];
        }else if(isset($response->returnStatus->status) && strtolower($response->returnStatus->status)=='fail'){
            return ['status'=>'failure','error'=>$response->returnStatus->message,'msg'=>$response->returnStatus->message];
        }
        return ['status'=>'failure','error'=>'failed to get services','msg'=>'failed to get services'];

    }

    private static function get_city_code_through_name($city_name)
    {
        $city_name=strtolower($city_name);
        $cities= self::getCities();

        if($cities){
            foreach($cities as $city)
            {
                if(strtolower($city->cityName)==$city_name || strtolower($city->cityID)==$city_name){
                    return $city->cityName;
                }

            }

        }
        return false;
    }
    /***
     * check params before shipping submit
     */
    private static function check_shipping_params($params)
    {

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

    public static function trackShipping($courier=null,$order_ref_code=null)
    {
      //  echo $order_ref_code;exit;
        if($courier && $order_ref_code):
            self::set_config($courier);

            $header = array('X-IBM-Client-Id: ' . self::$courier_config->client_id, 'accept:application/json');
            $request_url=rtrim(self::$api_url, "/")."/track/v1/shipments/detail?consignmentNumber=".$order_ref_code;
            $response =  self::makeGetApiCall($request_url,$header);
          //  echo $response;
            $response=json_decode($response);
            //self::debug($response);
            if(isset($response->returnStatus->status) && $response->returnStatus->status == self::$success){
                return ['status' => 'success',
                    'courier_status' => isset($response->TrackDetailReply->DeliveryInfo[0]->status) ? strtolower($response->TrackDetailReply->DeliveryInfo[0]->status) : NULL, // current status
                    'expected_delivery_date' => NULL,
                    'shipping_history' =>isset($response->TrackDetailReply->Checkpoints) ? $response->TrackDetailReply->Checkpoints:NULL,
                    'msg' => '',
                    'error' => ''];
            }
        endif;
        return ['status'=>'failure','error'=>'failed to process','msg'=>'failed to process'];

        /////////////////
        ///
    }

}