<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 9/26/2019
 * Time: 5:10 PM
 */
namespace backend\util;

use common\models\Products;
use common\models\Settings;
use dosamigos\ckeditor\CKEditor;
use Yii;

class SkuVault extends \backend\controllers\MainController{

    private static $TenantToken = '';
    private static $UserToken = '';
    private static $current_warehouse_location=null; //for stock update set location of selected warehouse
    private static $db_log=[]; // to save log of request and response

    public static function SetToken($warehouse_id){

        $warehouse = \common\models\Warehouses::find()->where(['id'=>$warehouse_id,'is_active'=>'1'])->one();
        if ($warehouse){
            $configuration = json_decode($warehouse->configuration);
            self::$TenantToken = $configuration->TenantToken;
            self::$UserToken = $configuration->UserToken;
            return true;
        }
        else{
            return false;
        }
    }

    private static function set_current_warehouse_location($warehouse)
    {
        $locations=self::getLocations($warehouse->id);
        if(isset($locations->Items))
        {
            foreach($locations->Items as $k=>$loc)
            {
                if(strtolower($warehouse->name) == strtolower($loc->WarehouseName))
                {
                   self::$current_warehouse_location= $loc->LocationCode;
                   break;
                }
            }
        }
        return self::$current_warehouse_location;
    }

    public static function GetSkuStocks( $warehouse_id ){

        $wareHouseStatus = self::SetToken($warehouse_id); // Set tokens first
        if($wareHouseStatus == false){
            return [];
        }
        $access = ['accept:application/json','content-type: application/x-www-form-urlencoded'];
        $url = "https://app.skuvault.com/api/inventory/getAvailableQuantities";

        $postFields = [
            'TenantToken' => self::$TenantToken,
            'UserToken'   => self::$UserToken
        ];

        $response = self::_MakeCall($url,$access,'POST','',http_build_query($postFields));
        return json_decode($response);


    }

    public static function getWarehouseItemQuantities( $warehouse_id, $params ){

        $wareHouseStatus = self::SetToken($warehouse_id); // Set tokens first
        if($wareHouseStatus == false){
            return [];
        }

        $access = ['accept:application/json','content-type: application/x-www-form-urlencoded'];
        $url = "https://app.skuvault.com/api/inventory/getWarehouseItemQuantities";

        $postFields = [
            'TenantToken' => self::$TenantToken,
            'UserToken'   => self::$UserToken
        ];

        $postFields = array_merge($postFields, $params);

        $response = self::_MakeCall($url,$access,'POST','',http_build_query($postFields));
        return json_decode($response);


    }
    /////////////// get stock of all warehouses
    public static function getAllWarehouseItemQuantities( $warehouse_id, $params ){

        $wareHouseStatus = self::SetToken($warehouse_id); // Set tokens first
        if($wareHouseStatus == false)
            return [];


        $access = ['accept:application/json','content-type: application/x-www-form-urlencoded'];
        $url = "https://app.skuvault.com/api/inventory/getItemQuantities";

        $postFields = [
            'TenantToken' => self::$TenantToken,
            'UserToken'   => self::$UserToken,
            'PageSize'=> 5000
        ];

        $postFields = array_merge($postFields, $params);

        $response = self::_MakeCall($url,$access,'POST','',http_build_query($postFields));
        return json_decode($response);


    }
    ///
    public static function GetProducts($warehouse_id, $Filters=[]){

        $wareHouseStatus = self::SetToken($warehouse_id); // Set tokens first
        if($wareHouseStatus == false){
            return [];
        }
        $access = ['accept:application/json','content-type: application/x-www-form-urlencoded'];
        $url = "https://app.skuvault.com/api/products/getProducts";

        $postFields = [
            'TenantToken' => self::$TenantToken,
            'UserToken'   => self::$UserToken
        ];


        $postFields = array_merge($postFields, $Filters);

        $response = self::_MakeCall($url,$access,'POST','',http_build_query($postFields));
        return json_decode($response);

    }

    public static function _MakeCall($apiUrl, $authorizeHead = [], $method = 'GET', $curl_port = "", $post_fields = "")
    {
        $curl_url = sprintf("%s", $apiUrl);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_PORT => $curl_port,
            CURLOPT_URL => $curl_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 6000,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $post_fields,
            CURLOPT_HTTPHEADER => $authorizeHead,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER =>0
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
           // var_dump($err); die();
            echo "cURL Error #:" . $err . ' call from:' . $apiUrl;
        } else {
           // var_dump($response); die();
            return $response;
        }
    }

    public static function createProduct($warehouse_id, $params){
        $wareHouseStatus = self::SetToken($warehouse_id); // Set tokens first
        if($wareHouseStatus == false){
            return [];
        }
        $access = ['accept:application/json','content-type: application/x-www-form-urlencoded'];
        $url = "https://app.skuvault.com/api/products/createProduct";

        $postFields = [
            'TenantToken' => self::$TenantToken,
            'UserToken'   => self::$UserToken
        ];
        $params['Brand'] = 'adidas';
        $params['Classification'] = 'General';
        $params['Supplier'] = 'Double D Imports SAS';
        $postFields = array_merge($postFields, $params);
        $response = self::_MakeCall($url,$access,'POST','',http_build_query($postFields));
        $response = json_decode($response);

        return $response;
    }

    public static function createProducts($warehouse_id, $params){
        $wareHouseStatus = self::SetToken($warehouse_id); // Set tokens first
        if($wareHouseStatus == false){
            return [];
        }
        $access = ['accept:application/json','content-type: application/x-www-form-urlencoded'];
        $url = "https://app.skuvault.com/api/products/createProduct";

        $postFields = [
            'TenantToken' => self::$TenantToken,
            'UserToken'   => self::$UserToken
        ];
        /*$params['Brand'] = 'adidas';
        $params['Classification'] = 'General';
        $params['Supplier'] = 'Double D Imports SAS';*/
        $postFields = array_merge($postFields, $params);
        //echo '<prE>';
        //print_r($postFields);

        $response = self::_MakeCall($url,$access,'POST','',http_build_query($postFields));
        $response = json_decode($response);
        //print_r($response);
        //die;

        return $response;
    }

    public static function getWarehouses($warehouse_id)
    {
        $wareHouseStatus = self::SetToken($warehouse_id); // Set tokens first
        if($wareHouseStatus == false){
            return [];
        }
        $access = ['accept:application/json','content-type: application/x-www-form-urlencoded'];
        $url = "https://app.skuvault.com/api/inventory/getWarehouses";

        $postFields = [
            'TenantToken' => self::$TenantToken,
            'UserToken'   => self::$UserToken
        ];

        $response = self::_MakeCall($url,$access,'POST','',http_build_query($postFields));
        $response = json_decode($response);

        return $response;

    }

    public static function getLocations($warehouse_id)
    {
        $wareHouseStatus = self::SetToken($warehouse_id); // Set tokens first
        if($wareHouseStatus == false){
            return [];
        }
        $access = ['accept:application/json','content-type: application/x-www-form-urlencoded'];
        $url = "https://app.skuvault.com/api/inventory/getLocations";

        $postFields = [
            'TenantToken' => self::$TenantToken,
            'UserToken'   => self::$UserToken
        ];

        $response = self::_MakeCall($url,$access,'POST','',http_build_query($postFields));
        $response = json_decode($response);

        return $response;
    }

    public static function addItem( $warehouse_id, $params )
    {
        $wareHouseStatus = self::SetToken($warehouse_id); // Set tokens first
        if($wareHouseStatus == false){
            return [];
        }
        $access = ['accept:application/json','content-type: application/x-www-form-urlencoded'];
        $url = "https://app.skuvault.com/api/inventory/addItem";

        $postFields = [
            'TenantToken' => self::$TenantToken,
            'UserToken'   => self::$UserToken
        ];
        $postFields = array_merge($postFields, $params);

        $response = self::_MakeCall($url,$access,'POST','',http_build_query($postFields));
        $response = json_decode($response);

        return $response;
    }

    public static function SetItemQuantity($warehouse_id, $params )
    {
        $wareHouseStatus = self::SetToken($warehouse_id); // Set tokens first
        if($wareHouseStatus == false){
            return [];
        }
        //SetItemQuantity
        $access = ['accept:application/json','content-type: application/x-www-form-urlencoded'];
        $url = "https://app.skuvault.com/api/inventory/setItemQuantity";

        $postFields = [
            'TenantToken' => self::$TenantToken,
            'UserToken'   => self::$UserToken
        ];
        $postFields = array_merge($postFields, $params);

        $response = self::_MakeCall($url,$access,'POST','',http_build_query($postFields));
        $response = json_decode($response);

        return $response;

    }
    public function debug($value){
        echo "<pre>";print_r($value);die;
    }

    public static function createProductAndItemsInWarehouses($warehouse_id,$SystemProducts)
    {
        $Filter['PageSize'] = 10000;
        $findOnSkuVault = SkuVault::GetProducts($warehouse_id); // Find if already exsit
        //$SystemProducts = HelpUtil::GetProductsForWarehouseSync($warehouse_id);
        $AlreadyExist = [];
       // $NewCreated = [];
        $error_log=[];
        $success_log=[];
        foreach ($findOnSkuVault->Products as $key=>$value){
            $AlreadyExist[] = trim($value->Sku);
        }
        //self::debug($AlreadyExist);
        foreach ( $SystemProducts as $syskey=>$prdval ){

            if ( !in_array($prdval['Sku'],$AlreadyExist) && $prdval['Code']!='' ){
                //$NewCreated["success"][] = "SKU: " . $prdval['Sku'] . " code: " . $prdval['Code'];
                $addProduct = SkuVault::createProduct($warehouse_id,$prdval); // Create product on SkuVault
                if ( isset($addProduct->Status) && $addProduct->Status=='OK' )
                {
                    $ezcom_to_warehouse_response=['warehouse_id'=>$warehouse_id,'status'=>'synced','sku'=>$prdval['Sku'],'comment'=>'product synced to warehouse'];
                    WarehouseUtil::ezcomToWarehouseProductSyncResponse($ezcom_to_warehouse_response);
                   // $NewCreated["success"][] = $prdval['Sku'];
                }
                elseif(isset($addProduct->Status) && $addProduct->Status=='BadRequest')
                {
                    $ezcom_to_warehouse_response=['warehouse_id'=>$warehouse_id,'status'=>'failed','sku'=>$prdval['Sku'],'response'=>$addProduct->Errors[0],'comment'=>'failed product synced to warehouse'];
                     WarehouseUtil::ezcomToWarehouseProductSyncResponse($ezcom_to_warehouse_response);
                    $error_log[$prdval['Sku']]=$addProduct->Errors[0];  // error msg
                    //$NewCreated["fail"][] = "SKU: " . $prdval['Sku']. "EAN Code: " . $prdval['Code'] . " Error: " . $addProduct->Errors[0];
                }else{
                    $error_log[$prdval['Sku']]=$addProduct;  // error msg
                }
                sleep(6);
            }
        }
        /*if (empty($NewCreated)){
            $NewCreated["success"][] = '<h5>System didn"nt found any product to create on "'.$_GET['name'].'" - SkuVault. All products are already synced with this warehouse. </h5>';
        }*/
        return ['error_log'=>$error_log,'success_log'=>$success_log];
       // return json_encode($NewCreated);
    }

    public static function createPO( $warehouse_id , $request )
    {
        $wareHouseStatus = self::SetToken($warehouse_id); // Set tokens first
        if($wareHouseStatus == false){
            return [];
        }
        $access = ['accept:application/json','content-type: application/json'];
        $url = "https://app.skuvault.com/api/purchaseorders/createPO";

        $postFields = [
            'TenantToken' => self::$TenantToken,
            'UserToken'   => self::$UserToken
        ];
        $params = array_merge($postFields,$request);
        $response = self::_MakeCall($url,$access,'POST','',json_encode($params));
        $response = json_decode($response);

        return $response;
    }

    public function updateBulkStock($warehouse,$sku_list)
    {

        $prepared=array();
        if(count($sku_list) <= 100)
        {
           self:: updateBulkStockCall($warehouse,$sku_list);
        }
        else
        {
            $count=0;
            foreach ($sku_list as $sku)
            {
                $prepared[]=$sku;
                if(fmod(++$count,100)==0)  //100 per request
                {
                    self:: updateBulkStockCall($warehouse,$prepared);
                    $prepared=array();
                }

            }
            if($prepared) // remaining left behind from 100 batch
            {
                self:: updateBulkStockCall($warehouse,$prepared);
            }
        }
        return;
    }
    ///update stock to skuvault fba warehouse bulk
    public static function updateBulkStockCall($warehouse,$sku_list) //max 100 per call
    {

        $header = ['accept:application/json','content-type: application/json'];
        $url = "https://app.skuvault.com/api/inventory/setItemQuantities";
        $warehouse_settings=json_decode($warehouse['configuration']);
        $warehouse_location=self::$current_warehouse_location ? self::$current_warehouse_location:self::set_current_warehouse_location($warehouse);
        $items=array();
        foreach($sku_list as $k=>$val)
        {
            $items[]=['sku'=>$val['sku'],'LocationCode'=>$warehouse_location ? $warehouse_location:'UK' ,'Quantity'=>$val['available'],'WarehouseId'=>$warehouse_settings->code];
        }
        $prepared_fields=[
                        'Items'=>$items,
                        'TenantToken'=>$warehouse_settings->TenantToken,
                        'UserToken'=>$warehouse_settings->UserToken
                        ];

        $post_fields=json_encode($prepared_fields);;
        $response = self::_MakeCall($url,$header,'POST','',$post_fields);
        return $response;
    }

    ////map order status for skuvault syncsales
    private static function mapOrderStatus($order_status)
    {

        $completed_orders = ['delivered', 'shipped','completed'];
        $canceled_orders = ['cancelled', 'canceled','refunded'];
        $pending_orders = ['pending','unshipped','awaiting check','awaiting paypal payment', 'payment accepted','processing in progress','remote payment accepted','payment error'];
        if(in_array(strtolower($order_status),$completed_orders))
        {

            return array(
                        'checkout_status'=>'',
                        'payment_status'=>'',
                        'sale_status'=>'',
                        'shipping_status'=>'Shipped',
            );
        }
       elseif(in_array(strtolower($order_status),$canceled_orders))
        {
            return array(
                        'checkout_status'=>'',
                        'payment_status'=>'',
                        'sale_status'=>'Cancelled',
                        'shipping_status'=>'',
                    );
        }
       elseif(in_array(strtolower($order_status),$pending_orders))
        {
            return array(
                'checkout_status'=>'pending',
                'payment_status'=>'',
                'sale_status'=>'Active',
                'shipping_status'=>'PendingShipment',
            );
        }
        return array(
            'checkout_status'=>'',
            'payment_status'=>'',
            'sale_status'=>'',
            'shipping_status'=>'',
        );
    }



    private  function getOrderItems($order_id,$channel_detail)
    {
        //echo $channel_id; die();

        //$order_items=  \common\models\OrderItems::find(['order_id'=>$order_id])->all();
        $fulfilled_items=array();
        $all_skus=array();
        $sql= "SELECT `oi`.`item_sku`,`oi`.`quantity`,`oi`.`price`,`oi`.`paid_price` ,`oi`.`item_status`,`cp`.`fulfilled_by`
                    FROM 
                    `order_items` oi
                    INNER JOIN 
                        `channels_products` cp
                        ON 
                            `cp`.`channel_sku`=`oi`.`item_sku`
                     WHERE
                        `oi`.`order_id`='".$order_id."' AND `cp`.`channel_id`='".$channel_detail['channel_id']."'";

        $order_items=Yii::$app->db->createCommand($sql)->queryAll();
        //var_dump($order_items); die();
        if($order_items)
        {
            foreach($order_items as $k=>$item){

                $current_item=[
                    'Quantity'=>$item['quantity'],
                    'Sku'=>$item['item_sku'],
                    'UnitPrice'=>$item['paid_price'],
                ];

                // if(strtolower(self::mapOrderStatus($item['item_status'])['checkout_status'])=="completed")
                // {
                //    $fulfilled_items[]=$current_item;
                //   }
                if ($channel_detail['marketplace']=='amazon' && $item['fulfilled_by']=='FBA') { //if amazon order and fullfilled by amazon itself no need to update qty in skuvault
                    $fulfilled_items[]=$current_item;
                } else {
                    $all_skus[]=$current_item;
                }
            }
        }

        return array(
            'all_skus'=>$all_skus,
            'fulfilled'=>$fulfilled_items,
        );
    }

    public function syncOnlineSale($time_period)
    {
         $order_list=array();
        $orders=self::getchannelordersAssociatedWithSkuvault($time_period);// get orders of channels associated with skuvault warehouse
        if($orders)
        {
            $index=$counter=0; // for making batch
            foreach($orders as $key=>$val) {
                $warehouse_code=json_decode($val['warehouse_config']);
                $order_items=self::getOrderItems($val['order_pk'],array('channel_id'=>$val['channel_id'],'marketplace'=>$val['marketplace']));
              // if(!$order_items['all_skus']){continue;} // if has no items then skip order
                $order_status=self::mapOrderStatus($val['order_status']);
                $order_data=array(
                    'CheckoutStatus'=>$order_status['checkout_status'],
                    'ItemSkus'=>$order_items['all_skus'],
                    'OrderDateUtc' => date('c',strtotime($val['order_created_at'])),
                    'MarketplaceId'=>$val['channel_name'],
                    'Notes'=>$val['channel_name'],
                    'OrderId' => $val['order_number'],
                    'OrderTotal' => $val['order_total'],
                    'PaymentStatus' =>$order_status['payment_status'],
                    'SaleState' => $order_status['sale_status'],
                    'AutoRemoveInfo' => array (
                        'AutoRemove' => true, // false=>not increase decrease , true=> increase decrease stock
                        'WarehouseCode' => array (
                            'Code' => isset($warehouse_code->code) ? $warehouse_code->code:'',
                        ),
                        'WarehouseName' => array (
                            'Name' => $val['warehouse_name'],
                        ),
                    ),

                    'ShippingInfo' =>
                        array (
                            'City' => $val['shipping_city'],
                            'Country' => $val['shipping_country'],
                            'FirstName' => $val['shipping_fname'],
                            'LastName' => $val['shipping_lname'],
                            'Line1' => $val['shipping_address'],
                            'Postal' => $val['shipping_post_code'],
                            'ShippingStatus' =>$order_status['shipping_status'],
                        ),
                   // 'TenantToken' => $warehouse_code->TenantToken,
                  //  'UserToken' => $warehouse_code->UserToken,
                );
                if($order_items['fulfilled']){
                    $order_data['FulfilledItems']=$order_items['fulfilled'];
                }


                  if(fmod($counter,100)==0 && $counter > 0)  // batch update 100 per call
                            $index++;

                         $order_list[$index][]=$order_data;
                        $counter++;



            }  // foreach end
            self::syncOnlineSaleAction($order_list,$warehouse_code); //api call
        }

        return self::$db_log;
    }

    ///////sync online sales api call
    private  function syncOnlineSaleAction($order_data,$warehouse_code)
    {
        $loop_execution=0;
        if ($order_data):
            foreach ($order_data as $index => $batch_number) {  // per index 100 batch update

                if(fmod($loop_execution,2)==0 && $loop_execution > 0)  // per minute 2 calls allowed
                    sleep(80);

                $list = [];
                foreach ($batch_number as $single_array)
                    $list['sales'][] = $single_array;

                $list['TenantToken'] = $warehouse_code->TenantToken;
                $list['UserToken'] = $warehouse_code->UserToken;

                //// send to api
                $header = ['accept:application/json','content-type: application/json'];
                $url = "https://app.skuvault.com/api/sales/syncOnlineSales";
                $post_fields=json_encode($list);
                 $response = self::_MakeCall($url,$header,'POST','',$post_fields);
                // for dblog
                $list_log['bulk_skuvault_orders'][]=$list;
                self::$db_log[]=[
                    'request'=>array($list_log,'additional_info'=>array('type'=>'order syncing to sku vault','time'=>date('Y-m-d H:i:s'))),
                    'response'=>self::get_json_decode_format($response),
                ];

                $loop_execution++; // to check throtling
            }


        endif;
            return;

    }

    public static function getchannelordersAssociatedWithSkuvault($time_period)
    {
        $current_time= gmdate('Y-m-d H:i:s');  //utc
        $and_where="";
        if($time_period=="day"){
            $from = gmdate("Y-m-d H:i:s", (time() -(1440*60))); // 24 hours
            $and_where="AND `order_updated_at` BETWEEN '".$from."' AND '".$current_time."'";

        }
        elseif($time_period=="chunk"){
            $from = gmdate("Y-m-d H:i:s",(time() -(25*60))); //  25 minutes
            $and_where=" AND `order_updated_at` BETWEEN '".$from."' AND '".$current_time."'";
        }
        $encryptionKey =Settings::GetDataEncryptionKey();
        // getting orders and customer address of channel attached with skuvault warehouse
        $sql="SELECT `wh`.`name` as warehouse_name ,`wh`.`configuration` as warehouse_config, `ch`.`name` as channel_name ,`ch`.`id` as channel_id,`ch`.`marketplace`,
                      o.id as order_pk, o.order_number ,  o.order_total, o.order_status ,o.order_created_at,
                      AES_DECRYPT(ca.shipping_fname,'".$encryptionKey."') as shipping_fname ,  
                      AES_DECRYPT(ca.shipping_lname,'".$encryptionKey."') as shipping_lname ,
                      AES_DECRYPT(ca.shipping_address,'".$encryptionKey."') as shipping_address ,  
                      AES_DECRYPT(ca.shipping_state,'".$encryptionKey."') as shipping_state ,
                        AES_DECRYPT(ca.shipping_city,'".$encryptionKey."') as shipping_city ,
                         AES_DECRYPT(ca.shipping_post_code,'".$encryptionKey."') as shipping_post_code , 
                      AES_DECRYPT(ca.shipping_country,'".$encryptionKey."') as shipping_country  
                FROM
                    `warehouses` wh
                INNER JOIN 
                    `warehouse_channels` whc
                 ON 
                    `wh`.`id`=`whc`.`warehouse_id`
                INNER JOIN
                     `channels` ch
                ON 
                    `ch`.`id`=`whc`.`channel_id`
                INNER JOIN
                    `orders` o 
                ON 
                    `o`.`channel_id`=`ch`.`id`
                INNER JOIN
                    `customers_address` ca
                ON 
                    `ca`.`order_id`=`o`.`id`
                WHERE 
                    LOWER(`wh`.`warehouse`)='skuvault'
                    $and_where";
        //echo $sql; die();
        return Yii::$app->db->createCommand($sql)->queryAll();
    }

    public static function getPOs($warehouse_id, $filters=[])
    {
        $wareHouseStatus = self::SetToken($warehouse_id); // Set tokens first
        if($wareHouseStatus == false){
            return [];
        }
        $access = ['accept:application/json','content-type: application/json'];
        $url = "https://app.skuvault.com/api/purchaseorders/getPOs";

        $postFields = [
            'TenantToken' => self::$TenantToken,
            'UserToken'   => self::$UserToken
        ];

        $params = array_merge($postFields,$filters);

        $response = self::_MakeCall($url,$access,'POST','',json_encode($params));
        $response = json_decode($response);

        return $response;

    }

    private  function get_json_decode_format($response)
    {
        if($response && is_string($response)) {

            $converted=json_decode($response);
            return (json_last_error() === JSON_ERROR_NONE) ? $converted :$response;
        }
        return $response;
    }

    /*
     * make pending stock array
     */

    public function make_pending_stock_list($all_warehouses_stock=null)
    {
        $list=[];
        if($all_warehouses_stock)
        {
            foreach($all_warehouses_stock->Items as $key=>$val)
                $list[$val->Sku]=['pending'=>$val->PendingQuantity];

        }
        return $list;
    }

    /*
     * to minus pending stock from stock to handle pending stock issue redford
     */
    public static function make_final_stock_list($warehouse_stock,$all_Warehouses_pending_stock)
    {

        $final_list=[];
        if(empty($all_Warehouses_pending_stock))  // if pending not set then return stock without depleting pending
                return $warehouse_stock;

        foreach($warehouse_stock as $key=>$val)
        {
            $available=$val['available'];
            if(isset($all_Warehouses_pending_stock[$val['sku']]['pending']))
                $available=($available - $all_Warehouses_pending_stock[$val['sku']]['pending']);

             $available=$available < 0 ? 0:$available; // if in minus set to 0
             $final_list[]=['sku'=>$val['sku'],'available'=>$available];
        }
        return $final_list;
    }
    public static function add_log($log,$warehouse_id,$type)
    {
        $data=[];
        if(isset($log['error_log']) && $log['error_log'])
        {
            foreach($log['error_log'] as $entity=>$error)
            {
                $data=['type'=>$type,  //'ezcom-to-warehouse-product-sync','ezcom-to-warehouse-order-sync'
                    'entity_type'=>'warehouse',
                    'entity_id'=>$warehouse_id,
                    'request'=>$entity, // sku or order_id
                    'response'=>$error,
                    'additional_info'=>NULL,
                    'log_type'=>'error',
                    'url'=>NULL];
                LogUtil::add_log($data);
            }
        }

        if(isset($log['success_log']) && $log['success_log'])
        {
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