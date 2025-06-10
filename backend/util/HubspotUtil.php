<?php
namespace backend\util;

use common\models\EzcomToWarehouseSync;
use common\models\Warehouses;

use Yii;

class HubspotUtil
{
    private static $current_channel = null;
    private static $api_url = "https://api.hubapi.com";
    private static $hubspotRef = 69;

    public static function makePostApiCall($params, $url, $headers, $method="POST")
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // Check HTTP status code
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($http_code == 200) {
            return ["status" => "success", "msg" => $http_code, "response" => json_decode($response)];
        } else {
            return ["status" => "fail", "msg" => "$http_code", "response" => json_decode($response)];
        }
        curl_close($ch);
    }


    private static function makeGetApiCall($requestUrl, $headers)
    {
        $ch = curl_init($requestUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET"); //bk
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($http_code == 200) {
            return ["status" => "success", "msg" => $http_code, "response" => json_decode($response)];
        } else {
            return ["status" => "fail", "msg" => "$http_code", "response" => json_decode($response)];
        }
        return $result;  //json format
    }

    public static function set_token($params)
    {
        self::$current_channel = json_decode($params['configuration']);
        $fields = 'grant_type=refresh_token&client_id=' . self::$current_channel->client_id . '&client_secret=' . self::$current_channel->client_secret . '&refresh_token=' . self::$current_channel->refresh_token;
        $url = self::$api_url . "/oauth/v1/token";
        $headers = array('Content-Type: application/x-www-form-urlencoded;charset=utf-8');
        $access_token = self::makePostApiCall($fields, $url, $headers);

        $arr_conf = array();
        $update_time = self::$current_channel->time;
        // getting current date
        $cDate = strtotime(date('Y-m-d H:i:s'));
        $oldDate = $update_time + 21600;

        if ($oldDate > $cDate) {
            return self::$current_channel = json_decode($params['configuration']);
        } else {
            $arr_conf['client_id'] = self::$current_channel->client_id;
            $arr_conf['client_secret'] = self::$current_channel->client_secret;
            $arr_conf['refresh_token'] = self::$current_channel->refresh_token;
            $arr_conf['access_token'] = $access_token['response']->access_token;
            $arr_conf['time'] = $cDate;
            $updateWarehouse = Warehouses::findOne($params['id']);
            $updateWarehouse->configuration = json_encode($arr_conf);
            $updateWarehouse->update();
            return self::$current_channel = (object)$arr_conf;
        }

    }


    public static function syncProductsToWarehouse($warehouse_id,$SystemProducts)
    {
        self::set_token($warehouse_id);
       // $SystemProducts = WarehouseUtil::getWarehouseProductsNotSynced($warehouse_id['id']);

        $error_log = [];
        $success_log = [];

        $url = self::$api_url . "/crm-objects/v1/objects/products";
        foreach ($SystemProducts as $syskey => $prdval) {
            $addProduct = HubspotUtil::createProduct($prdval, $url); // Create product on Sage

            if (isset($addProduct['status']) && $addProduct['status'] == "success") {
                $ezcom_to_warehouse_log = ['warehouse_id' => $warehouse_id['id'], 'status' => 'synced', 'sku' => $prdval['sku'], 'third_party_id' => $addProduct['response']->objectId,'comment' => 'product synced to warehouse'];
                WarehouseUtil::ezcomToWarehouseProductSyncResponse($ezcom_to_warehouse_log);
            } else {
                $ezcom_to_warehouse_log = ['warehouse_id' => $warehouse_id['id'], 'status' => 'failed', 'sku' => $prdval['sku'], 'response' => $addProduct['response'],'comment' => 'failed product synced to warehouse'];
                WarehouseUtil::ezcomToWarehouseProductSyncResponse($ezcom_to_warehouse_log);
                $error_log[$prdval['sku']] = $addProduct['response'];  // error msg
            }
        }

        return ['error_log' => $error_log, 'success_log' => $success_log];
    }

    public static function createProduct($params, $url)
    {

        $headers = self::getHeader();
        $arr = array();
        $arr['0'] = array('name' => 'name', 'value' => $params['name']);
        $arr['1'] = array('name' => 'hs_sku', 'value' => $params['sku']);
        $arr['2'] = array('name' => "price", 'value' => $params['rccp']);
        $response = self::makePostApiCall(json_encode(array($arr)), $url, $headers);
        return $response;
    }


    public function syncSalesToWarehouse($time_period, $warehouse)
    {
        self::set_token($warehouse);
        $error_log = [];
        $success_log = [];
        $property_arr = array();
        $associations = array();
        $arr = array();

        $orders = OrderUtil::getWarehouseOrders($warehouse['id'], $time_period); // get warehouse orders
        $pushed_orders = self::getOrdersAlreadyPushedToWarehouse($warehouse['id'], $orders);  // already pushed orders to quick book
      //  echo "<pre>";print_r($orders);exit;
        foreach ($orders as $order_id => $value) {
            if(isset($pushed_orders[$order_id]) && $pushed_orders[$order_id]['pushing_time_status']==$value['detail']['order_status'])
            {
                continue; // same status as previuos one uploaded
            }elseif(isset($pushed_orders[$order_id]) && in_array($value['detail']['order_status'],['pending','deliver','delivered','complete','completed','shipped'])){

                $update_status = self::updateStatus($value['detail']['order_status'], $pushed_orders, $order_id);
                if($update_status['status']=="failure")
                    $error_log[$value['detail']['order_pk_id']]=$update_status['status'];
                continue;

            }elseif(isset($pushed_orders[$order_id]) && in_array($value['detail']['order_status'],['cancelled','cancel','canceled'])){
                $update_status = self::updateStatus($value['detail']['order_status'], $pushed_orders, $order_id);
                if($update_status['status']=="failure")
                    $error_log[$value['detail']['order_pk_id']]=$update_status['status'];
                continue;

            } elseif(in_array($value['detail']['order_status'],['cancelled','cancel','canceled'])){
                continue; // if new order and not already uploaded and status is cancel then no need to upload

            } else {

                $contact_id = self::getContact($value['customer']);
              //  echo $value['detail']['order_created_at'];exit;
                $close_date = strtotime($value['detail']['order_created_at']) * 1000;

                $associations['associatedCompanyIds'] = array('');
                $associations['associatedVids'] = array('401');

                $order_status = self::mapOrderStatus($value['detail']['order_status']);
               // echo "<pre>";print_r($order_status);exit;
                $arr['0'] = array('value' => '#' . $value['detail']['order_pk_id'], 'name' => 'dealname');
                $arr['1'] = array('value' => $order_status['stage'], 'name' => 'dealstage');
            //   $arr['2'] = array('value' => '12096400', 'name' => 'pipeline');
                $arr['2'] = array('value' => $order_status['pipeline'], 'name' => 'pipeline');
                $arr['3'] = array('value' => $close_date, 'name' => 'closedate');
                $arr['4'] = array('value' => $value['detail']['order_total'], 'name' => 'amount');
                $arr['5'] = array('value' => 'newbusiness', 'name' => 'dealtype');
                $property_arr['associations'] = $associations;
                $property_arr['properties'] = $arr;

                $data = ['associations' => $associations, 'properties' => $arr];

                $endpoint = self::$api_url . "/deals/v1/deal";
                $headers = self::getHeader();
                $responses = self::makePostApiCall(json_encode($data), $endpoint, $headers);
                $deal_id = null;
                if(isset($responses['status']) && $responses['status'] == "success"){
                    echo $deal_id = $responses['response']->dealId;
                }


                foreach ($value['order_items'] as $keys => $values) {

                    $sql = "SELECT * from ezcom_to_warehouse_sync
                    WHERE ezcom_entity_id = '".$values['item_sku']."'  AND warehouse_id = ".$warehouse['id'].";";

                    $result = Yii::$app->db->createCommand($sql)->queryOne();

                    if($result){
                        $product_id = $result['third_party_entity_id'];
                    }else{
                        $res = self::createProducts($values['item_sku'], $warehouse['id']);
                        $product_id = $res['product_id'];
                        $product_name = $res['product_name'];
                    }

                    $arr_order_item['0'] = array('name' => 'hs_product_id', 'value' => $product_id);
                    $arr_order_item['1'] = array('name' => 'quantity', 'value' => $values['quantity']);
                    $arr_order_item['2'] = array('name' => 'price', 'value' => $values['price']);
                    $arr_order_item['3'] = array('name' => 'hs_sku', 'value' => $values['item_sku']);
                    $arr_order_item['4'] = array('name' => 'name', 'value' => $product_name);

                    $endpoint_line_item = self::$api_url . "/crm-objects/v1/objects/line_items";
                    $headers = self::getHeader();

                    $responsese = self::makePostApiCall(json_encode($arr_order_item), $endpoint_line_item, $headers);
                    $associate_deal = self::$api_url."/crm-associations/v1/associations";
                    $crm = array("fromObjectId" => $deal_id, "toObjectId" => $responsese['response']->objectId, "category" => "HUBSPOT_DEFINED", "definitionId" => 19);

                    $headers = self::getHeader();
                    $associate_deal = self::makePostApiCall(json_encode($crm), $associate_deal, $headers, "PUT");

                }

                $associate_deal_endpoint = self::$api_url . "/deals/v1/deal/".$deal_id."/associations/CONTACT?id=".$contact_id;
                $headers = self::getHeader();
                $associate_deal = self::makePostApiCall(json_encode(array()), $associate_deal_endpoint, $headers, "PUT");

                if(isset($responses['status']) && $responses['status'] == "success"){
                    $ezcom_to_warehouse_log = ['warehouse_id' => $warehouse['id'], 'type' => 'order', 'ezcom_entity_id' => $value['detail']['order_pk_id'], 'third_party_entity_id' => $responses['response']->dealId, 'ezcom_status' => $value['detail']['order_status'], 'comment' => 'Order synced to warehouse'];
                    LogUtil::ezcomToWarehouseSyncedLog($ezcom_to_warehouse_log); // save record of product created to online warehouse
                    $success_log[$value['detail']['order_pk_id']]=$responses['response']->dealId; // for general log
                } else {
                    return ['status'=>'failure','msg'=>$responses];
                }

            }
        }

        return ['error_log' => $error_log, 'success_log'=>$success_log];
    }


//    public static  function pipeline(){
//
//        $url = self::$api_url."/deals/v1/pipelines"; ///crm/v3/pipelines
//        $headers = self::getHeader();
//        $response = self::makeGetApiCall($url, $headers);
//         echo "<pre>";print_r($response);exit;
//
//    }

    public static  function updateStatus($order_status, $push_order, $order_id){

        $get_order_status_id = self::mapOrderStatus($order_status);
        $url = self::$api_url."/deals/v1/deal/".$push_order[$order_id]['third_party_entity_id'];
        $headers = self::getHeader();
        $arr = [];
        $data[] = ['value' => $get_order_status_id['stage'], 'name' => 'dealstage'];
        $arr['properties'] = $data;

        $response = self::makePostApiCall(json_encode($arr), $url, $headers, "PUT");
       // echo "<pre>";print_r($response);exit;

         return $response;
    }


    //getting to orders and create product
    public static function createProducts($sku, $warehouse_id){

        $success_log = [];
        $error_log = [];
        $url = self::$api_url . "/crm-objects/v1/objects/products";

        $sql = "SELECT * from products WHERE sku = '".$sku."';";
        $result=Yii::$app->db->createCommand($sql)->queryOne();
        $headers = self::getHeader();
        $arr['0'] = array('name' => 'name', 'value' => $result['name']);
        $arr['1'] = array('name' => 'hs_sku', 'value' => $result['sku']);
        $arr['2'] = array('name' => "price", 'value' => $result['rccp']);

        $response = self::makePostApiCall(json_encode(array($arr)), $url, $headers);

        if (isset($response['status']) && $response['status'] == "success") {
            $ezcom_to_warehouse_log = ['warehouse_id' => $warehouse_id, 'type' => 'product', 'ezcom_entity_id' => $result['sku'], 'third_party_entity_id' => $response['response']->objectId, 'comment' => 'product synced to warehouse'];
            LogUtil::ezcomToWarehouseSyncedLog($ezcom_to_warehouse_log); // save record of product created to online warehouse

            $success_log[$result['sku']] = $response['response']->objectId; // general log
            $db_log = ['success_log' => $success_log];

            self::add_log($db_log, $warehouse_id, 'ezcom-to-warehouse-product-sync');
            return array("product_id" => $response['response']->objectId, "product_name" => $result['name']);
        }else{
           $error_log[$result['sku']] = $response['response']; // general log
            $db_log = ['error_log' => $error_log];
            self::add_log($db_log, $warehouse_id, 'ezcom-to-warehouse-product-sync');
        }

    }

    public static function getContact($getCustomer){
        $endpoint = self::$api_url . "/contacts/v1/contact/email/".$getCustomer['shipping_email']."/profile";

        $headers = self::getHeader();
        $hubspot_contacts = self::makeGetApiCall($endpoint, $headers);
        if($hubspot_contacts['status'] == "success"){
            return $hubspot_contacts['response']->vid;
        }else{
            $arr = array(
                'properties' => array(
                    array(
                        'property' => 'email',
                        'value' => $getCustomer['shipping_email']
                    ),
                    array(
                        'property' => 'firstname',
                        'value' => $getCustomer['shipping_fname']
                    ),
                    array(
                        'property' => 'lastname',
                        'value' => $getCustomer['shipping_lname'],
                    ),
                    array(
                        'property' => 'phone',
                        'value' => $getCustomer['shipping_phone']
                    )
                )
            );
            $endpoint_contacts = self::$api_url . "/contacts/v1/contact";
            $headers = self::getHeader();
            $response = self::makePostApiCall(json_encode($arr), $endpoint_contacts, $headers);
            return $response['response']->vid;
        }


        }






    public static function getOrdersAlreadyPushedToWarehouse($warehouse_id,$ezcom_orders)
    {
        $pushed=[];
        if($ezcom_orders && is_array($ezcom_orders))
        {
            $order_ids=array_keys($ezcom_orders);
            $pushed_orders=EzcomToWarehouseSync::find()->select('ezcom_entity_id,third_party_entity_id,ezcom_status as pushing_time_status')->Where(['IN', 'ezcom_entity_id', $order_ids])->andWhere(['warehouse_id'=>$warehouse_id])->andWhere(['type'=>'order'])->asArray()->all();
            if($pushed_orders)
            {
                foreach($pushed_orders as $order)
                {
                    $pushed[$order['ezcom_entity_id']]=$order;
                }
            }
        }

        return $pushed;
    }


    public static function getHeader(){
        return $headers= array('Content-Type: application/json',
            "Authorization: Bearer ".self::$current_channel->access_token
        );
    }


    public static function mapOrderStatus($order_status)
    {
        $url = self::$api_url."/deals/v1/pipelines"; ///crm/v3/pipelines
        $headers = self::getHeader();
        $status = self::makeGetApiCall($url, $headers);

        if (isset($status['status']) && $status['status'] == "success") {

            foreach ($status["response"] as $key => $pipeline) {

                if($pipeline->label == "Ecommerce Pipeline"){
                    $pipeline_id = $pipeline->pipelineId;

                    foreach ($pipeline->stages as $key => $stage){

                        if($stage->label == $order_status){
                               return array("pipeline" => $pipeline_id, 'stage' => $stage->stageId);
                               break;
                        }

                    }
                }
            }
        }

    }

    public static function getMapStatus($order_status){
        $completed_orders = ['shipped','deliver', 'delivered', 'completed','complete'];
        $canceled_orders = ['cancelled', 'canceled', 'cancel'];
        $pending_orders = ['pending', 'processing'];

        if(in_array(strtolower($order_status),$completed_orders))
            return "12096405";
        if(in_array(strtolower($order_status),$canceled_orders))
            return "12096406";
        if(in_array(strtolower($order_status),$pending_orders))
            return "12096407";

        return $order_status;
    }


    public static function add_log($log,$warehouse_id,$type)
    {

        $data=[];
        if(isset($log['error_log']) && $log['error_log'])
        {
            foreach($log['error_log'] as $entity=>$error)
            {
                $message = (array)$error;
                $data=['type'=>$type,  //'ezcom-to-warehouse-product-sync','ezcom-to-warehouse-order-sync'
                    'entity_type'=>'warehouse',
                    'entity_id'=>$warehouse_id,
                    'request'=>$entity, // sku or order_id
                    'response'=>$message['message'],
                    'additional_info'=>NULL,
                    'log_type'=>'error',
                    'url'=>NULL];
                LogUtil::add_log($data);
            }
        }

        if(isset($log['success_log']) && $log['success_log'])
        {
        //    echo "<pre>";print_r($log['success_log']);exit;
            $total_items=count($log['success_log']);
            $data=['type'=>$type, //'ezcom-to-warehouse-product-sync','ezcom-to-warehouse-order-sync'
                'entity_type'=>'warehouse',
                'entity_id'=>$warehouse_id,
                'request'=>$total_items ." requested",
                'response'=>$total_items ." added",
                'additional_info'=>'count of sum of entities added in log',
                'log_type'=>'info',
                'url'=>NULL];
            LogUtil::add_log($data);
        }
        return;

    }
}
