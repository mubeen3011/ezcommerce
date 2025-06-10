<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 2/19/2019
 * Time: 1:35 PM
 */
namespace backend\util;

use backend\controllers\ApiController;
use common\models\Channels;
use common\models\ChannelsProducts;

class ShopsUtil{
    public static function getProductDetail( $Sku, $Shop_Id ){
        // Lazada
        $lazada_shops = [19,1,10,13,15];
        if ( in_array($Shop_Id,$lazada_shops) )
            $Product_Detail = self::GetLazadaSkuInfo($Shop_Id,$Sku);

        $shopee_shops = [2,11,16];
        if ( in_array($Shop_Id,$shopee_shops) )
            $Product_Detail = self::GetShopeeSkuInfo($Shop_Id,$Sku);

        $street_shops = [14,3,9];
        if ( in_array($Shop_Id,$street_shops) )
            $Product_Detail = self::GetStreetSkuInfo($Shop_Id,$Sku);



        return ($Product_Detail);
    }

    public static function debug($data){
        echo '<pre>';
        print_r($data);
        die;
    }

    public static function GetLazadaSkuInfo( $Shop_Id , $sku_text ){

        $ch = Channels::findOne(['id' => $Shop_Id,'is_active'=>'1']);
        $auth_params = json_decode($ch->auth_params, true);

        $customParams['access_token'] = $auth_params['access_token'];
        $customParams['params']['sku_seller_list'] = json_encode([$sku_text]);
        //$customParams['params']['filter'] = 'live';
        $customParams['app_key'] = $ch->api_key;
        $customParams['app_secret'] = $ch->api_user;
        $customParams['method'] = 'GET';
        $customParams['action'] = '/products/get';

        $get_detail = self::_callLzdRequestMethod($customParams);
        /*if ($sku_text=='SCF566/27')
            self::debug($get_detail);*/
        return json_decode($get_detail);
    }

    public static function exchange_values($from, $to, $value, $table)
    {
        $connection = \Yii::$app->db;
        $get_all_data_about_detail = $connection->createCommand("select " . $to . " from " . $table . " where " . $from . " ='" . $value . "'");
        $result_data = $get_all_data_about_detail->queryAll();
        //return $result_data[0][$to];
        if (isset($result_data[0][$to])) {
            return $result_data[0][$to];
        } else {
            return 'false';
        }
    }

    public function _callLzdRequestMethod($params)
    {
        require_once __DIR__ . "/../../backend/util/lzdop/LazopSdk.php";

        $url = "https://auth.lazada.com/rest";
        $appkey = $params['app_key'];
        $appSecret = $params['app_secret'];
        $accessToken = $params['access_token'];
        $action = $params['action'];
        $method = $params['method'];
        $c = new \LazopClient($url, $appkey, $appSecret);
        $request = new \LazopRequest($action, $method);
        foreach ($params['params'] as $k => $v) {
            $request->addApiParam($k, $v);
        }
        return $c->execute($request, $accessToken);
    }
    public function GetShopeeSkuInfo( $channel_id , $Sku ){

        $Sku = self::GetParent($Sku);
        $obj = Channels::find()->where(['id' => $channel_id,'is_active'=>'1'])->one();

        // get the sku id according to shopee mapping
        $sql = "SELECT * FROM channels_products WHERE channel_sku = '$Sku' AND channel_id = $channel_id";
        $Sku = ChannelsProducts::findBySql($sql)->asArray()->all();

        if (!empty($Sku)){
            $apiKey = $obj->api_key;
            $apiUser = explode('|', $obj->api_user);
            $now = new  \DateTime();
            $postFields = [
                'item_id' => (int)$Sku[0]['sku'],
                'partner_id' => (int)$apiUser[0],
                'shopid' => (int)$apiUser[1],
                'timestamp' => $now->getTimestamp()

            ];

            $url = "https://partner.shopeemobile.com/api/v1/item/get";
            $postFields = json_encode($postFields);
            $authKey = hash_hmac('sha256', $url . '|' . $postFields, $apiKey);
            $access = ['Content-Type:application/json', 'Authorization:' . $authKey . ''];
            $response = ApiController::_ApiCall($url, $access, 'POST', "", $postFields);
            return json_decode($response);
        }
        else{
            return [];
        }

    }

    private function GetParent($Sku){
        $Parent_id=self::exchange_values('sku','parent_sku_id',$Sku,'products');
        if ($Parent_id==0)
            return $Sku;
        else
        {
            $Parent_Sku = self::exchange_values('id','sku',$Parent_id,'products');
            return $Parent_Sku;
        }
    }

    public function GetStreetSkuInfo($channel_id,$Sku){

        // get the sku id according to shopee mapping
        $sql = "SELECT * FROM channels_products WHERE channel_sku = '$Sku' AND channel_id = $channel_id";
        $Sku = ChannelsProducts::findBySql($sql)->asArray()->all();
        if (!empty($Sku)){
            $ch = Channels::findOne(['id' => $channel_id,'is_active'=>'1']);
            $apiKey = $ch->api_key;
            $access = ['openapikey: ' . $apiKey];

            $apiUrlx = "http://api.11street.my/rest/prodservices/product/details/".$Sku[0]['sku'];
            $response = ApiController::_ApiCall($apiUrlx, $access);
            $refinex = ApiController::_refineResponse($response);
            return $refinex;
        }
        else{
            return [];
        }


    }
}