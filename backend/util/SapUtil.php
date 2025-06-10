<?php
/**
 * Created by PhpStorm.
 * User: Dell
 * Date: 12/27/2020
 * Time: 9:49 PM
 */
namespace backend\util;

class SapUtil {

    private static $api_key=null;
    private static $api_url=null;


    private static function set_config()
    {
        self::$api_key = "DORG9brRmN3zoW1AtFcYFX3DH9O1VDPc";
        self::$api_url = "https://sandbox.api.sap.com/s4hanacloud/sap/opu/odata/sap/API_MATERIAL_STOCK_SRV/A_MatlStkInAcctMod";
        return array('api-key' => self::$api_key, 'api-url' => self::$api_url);

    }

    public static function makeGetApiCall(){

        $set_configuration = self::set_config();
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $set_configuration['api-url'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_POSTFIELDS => "------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"auth\"\r\n\r\nd5cc8d5f004d16990007707c00000001201c03764e0b4ea2c1fd562b276fd7b72a51b3\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"fields[DESCRIPTION]\"\r\n\r\nSolid Top Cap & Spark Arrestor Prevents Direct Rainfall & Animal Access To Flue\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"id\"\r\n\r\n1343\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW--",
            CURLOPT_HTTPHEADER => array(
                "accept: application/json",
                "cache-control: no-cache",
                "content-type: multipart/form-data; boundary=----WebKitFormBoundary7MA4YWxkTrZu0gW",
                "apikey: ".$set_configuration['api-key'],
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return "cURL Error #:" . $err;
        } else {
            return $response;
        }
    }


    public static function GetAllSkuStocks($warehouse){

        $product_detail = self::makeGetApiCall();

       return $someArray[] = json_decode($product_detail, true);


    }





}