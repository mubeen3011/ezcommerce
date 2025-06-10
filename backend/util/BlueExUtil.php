<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2/11/2020
 * Time: 11:33 AM
 */
namespace backend\util;
use common\models\Settings;

class BlueExUtil
{
    private static $courier=null;
    private static $courier_config=null;

    private static function set_config($courier)
    {
        self::$courier=$courier;
        self::$courier_config=json_decode($courier->configuration);
        return;
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

    private function makePostApiCall($requestUrl,$body_enclosed=null,$headers=null,$method="POST")
    {
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, $requestUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if($body_enclosed)
         curl_setopt($ch,CURLOPT_POSTFIELDS, $body_enclosed);
        if($headers)
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
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

        $customer_city=trim(strtolower($address['city']));
        $cities= self::getCities();
        if($cities)
        {
            foreach($cities as $city)
            {
                if(strtolower($city->city_name->{0})==$customer_city || strtolower($city->city_code->{0})==$customer_city){
                    return ['status'=>'success','msg'=>'validated','error'=>''];
                }

            }
            return ['status'=>'failure','msg'=>'Customer City ' . $address['city'] .' could not match , try assign from dropdown','error'=>'city mismatched'];
        }
    }

    private static function getCities()
    {
        $cities=Settings::findone(['name'=>'blueex_cities']); // check if cities are present in setttings table
        if($cities)
            return json_decode($cities->value);
        else{ // fetch and store in settings table
            $header = ['Content-Type:application/json'];
            $request_url="http://bigazure.com/api/demo/json/cities/serverjson.php";
            $cities_json=  self::makeGetApiCall($request_url,$header);
            if($cities_json)
                $cities=json_decode($cities_json);

            if(isset($cities->status) && isset($cities->success) && $cities->success && $cities->status)
            {
                $store= new Settings();
                $store->name="blueex_cities";
                $store->value=json_encode($cities->{'0'});
                $store->save();
                return $cities->{'0'};
            }
        }
        return false;
    }

    private static function get_city_code_through_name($city_name)
    {
        $city_name=strtolower($city_name);
        $cities= self::getCities();
        if($cities){
            foreach($cities as $city)
            {
                if(strtolower($city->city_name->{0})==$city_name || strtolower($city->city_code->{0})==$city_name){
                    return $city->city_code->{0};
                }

            }

        }
        return false;
    }

    public static function submitShipping($courier=null,$params=null)
    {
        //////////////////////////////////
        /*$request = new HttpRequest();
        $request->setUrl('http://bigazure.com/api/demo/json/serverjson.php');
        $request->setMethod(HTTP_METH_POST);

        $request->setHeaders(array(
            'postman-token' => '57834463-c489-7f70-af36-33ea2262d74c',
            'cache-control' => 'no-cache',
            'content-type' => 'application/x-www-form-urlencoded'
        ));

        $request->setContentType('application/x-www-form-urlencoded');
        $request->setPostFields(array(
            'request' => '{"acno":"KHI-00000","testbit":"y","userid":"demo","password":"demo123456","cn_generate":"y","customer_name":"Saad","customer_contact":"03229999589","customer_address":"lahore Pakistan,lahore Pakistan,lahore Pakistan","customer_city":"LHE","customer_country":"PK","customer_comment":"na","shipping_charges":"180","payment_type":"CC","shipper_origion_city":"KHI","total_order_amount":"3680","order_refernce_code":"000002365","products_detail":[{"product_code":"eb8061-2xl","product_name":"FL_SPR Z FT 3ST COLLEGIATE ROYAL 2XL","product_price":"3500.00","product_weight":"0.0","product_quantity":"1","sku_code":"eb8061-2xl"}]}'
        ));

        try {
            $response = $request->send();

            echo $response->getBody();
        } catch (HttpException $ex) {
            echo $ex;
        }
        die();*/
        /// ///////////////////////////////////
        $validation=self::check_shipping_params($params);
        if($validation['status']=='failure')
            return $validation;

        $shipper_city=self::get_city_code_through_name($params['shipper']['city']);
        if(!$shipper_city)
            return ['status'=>'failure','msg'=>'shipper City not found in BlueEx cities list'];

        self::set_config($courier);
        $to_send=[
            'acno'=>self::$courier_config->acno,
            'testbit'=>'n',
            'userid'=> self::$courier_config->{'User ID'},
  	        'password'=> self::$courier_config->Password,
  	        'cn_generate'=> 'y',
  	        'customer_name'=> $params['customer']['name'],
  	        'customer_email'=> (isset($params['customer']['email']) && $params['customer']['email']) ? $params['customer']['email']: 'abc@gmail.com',
  	        'customer_contact'=> $params['customer']['phone'],
  	        'customer_address'=> $params['customer']['address'],
  	        'customer_city'=> $params['customer']['city'],
  	        'customer_country'=> 'PK',
  	        'customer_comment'=> 'na',
  	        'shipping_charges'=> '0',
  	        'payment_type'=> (in_array(strtolower($params['order']->payment_method),['sample_gateway','hbl pay','online']) && $params['order']->order_market_status!='pending_payment') ? "CC":"COD",
  	        'shipper_origion_city'=> $shipper_city,
  	        'total_order_amount'=>isset($params['order_total']) ? $params['order_total'] :$params['order']->order_total,
  	        'order_refernce_code'=>$params['order']->order_number,

        ];
        $product_detail=[];
        ////items
        foreach($params['order_items'] as $item)
        {
            $item_name=\common\models\Products::find()->select('name')->where(['sku'=>$item['item_sku']])->scalar();
            $product_detail[]=[
                                'product_code'=>$item['item_sku'],
                                'product_name'=>$item_name ? $item_name:"xxxxx",
                                'product_price'=>$item['paid_price'],
                                'product_weight'=>'0.0',
                                'product_quantity'=>$item['quantity'],
                                'sku_code'=>$item['item_sku'],
                        ];
        }
        $to_send['products_detail']=$product_detail;
        $body_enclosed = "request=".urlencode(json_encode($to_send));
        $header = ['Content-Type:application/x-www-form-urlencoded'];
        $request_url="http://bigazure.com/api/demo/json/serverjson.php";
        $response=  self::makePostApiCall($request_url,$body_enclosed,$header,"POST");
        $response=json_decode($response);
       // echo "<pre>";
      //  print_r($response); die();
        if(isset($response->cn)){
            return [
                'status'=>'success',
                'amount_inc_taxes'=>$params['order_total'],
                'amount_exc_taxes'=>$params['order_total'],
                'tracking_number'=>$response->cn,
                'additional_info'=>['order_code'=>$response->order_code],
                'dimensions'=>isset($params['package']) ? $params['package']:NULL,  // ['length','width','height','weight']
                'label'=>'http://benefit.blue-ex.com/customerportal/inc/cnprnb.php?'.$response->cn, // blue ex not giving label
                'full_response'=>$response
            ];
        } elseif(isset($response->Error_message)){
            return ['status'=>'failure','error'=>$response->Error_message,'msg'=>$response->Error_message];
        }
        return ['status'=>'failure','error'=>'failed to get services','msg'=>'failed to get services'];
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
    /*
    * track shipping
    */
    public static function trackShippinghistory($courier=null,$tracking_number=null)
    {
        if($courier && $tracking_number):
            self::set_config($courier);

         $to_send=[
             'acno'=>self::$courier_config->acno,
             'userid'=> self::$courier_config->{'User ID'},
             'password'=> self::$courier_config->Password,
             'order_refernce_code'=>$tracking_number,
         ];
            $body_enclosed = "request=".urlencode(json_encode($to_send));
            $header = ['Content-Type:application/x-www-form-urlencoded'];
            $request_url="http://bigazure.com/api/demo/json/tracking/serverjson.php";
            $response=  self::makePostApiCall($request_url,$body_enclosed,$header,"POST");
            echo "<pre>";
            print_r($response); die();
        endif;
        return ['status'=>'failure','error'=>'failed to process','msg'=>'failed to process'];
    }
    /*
    * status of shipment
    */
    public static function trackShipping($courier=null,$order_ref_code=null)
    {
        if($courier && $order_ref_code):
            self::set_config($courier);

            $to_send=[
                'acno'=> self::$courier_config->acno,
                'userid'=> self::$courier_config->{'User ID'},
                'password'=> self::$courier_config->Password,
                'order_refernce_code'=>$order_ref_code,
            ];
            $body_enclosed = "request=".urlencode(json_encode($to_send));
            $header = ['Content-Type:application/x-www-form-urlencoded'];
            $request_url="http://bigazure.com/api/demo/json/status/serverjson.php";
            $response=  self::makePostApiCall($request_url,$body_enclosed,$header,"POST");
            $response=json_decode($response);
            //print_r($response); die();
            if(isset($response->success)){
                return ['status' => 'success',
                    'courier_status' => isset($response->message) ? $response->message : NULL, // current status
                    'expected_delivery_date' => NULL,
                    'shipping_history' =>NULL,
                    'msg' => '',
                    'error' => ''];
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

        $city_id=self::get_city_code_through_name(trim($address['customer_city']));  // lcs get id of that city
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

        $city_id=self::get_city_code_through_name($warehouse->city);  // lcs get id of that city
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